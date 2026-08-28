<?php

namespace Tests\Feature;

use App\Domain\Webhooks\WebhookDispatcher;
use App\Domain\Webhooks\WebhookSigner;
use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEvent;
use App\Jobs\SendWebhookJob;
use App\Models\ApiClient;
use App\Models\ApiKey;
use App\Models\Business;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Webhooks are the platform's promise to systems it does not control, so the
 * things that matter are: exactly-once delivery, verifiable authenticity, and
 * retries that eventually give up rather than hammering a dead endpoint.
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
    }

    #[Test]
    public function it_signs_a_payload_so_the_receiver_can_verify_it(): void
    {
        $signer = new WebhookSigner;

        $payload = '{"id":"evt_1","type":"order.delivered"}';
        $secret = 'whsec_test';
        $timestamp = now()->getTimestamp();

        $signature = $signer->sign($payload, $secret, $timestamp);

        $this->assertTrue($signer->verify($payload, $signature, $secret, $timestamp));
    }

    #[Test]
    public function a_tampered_payload_fails_verification(): void
    {
        $signer = new WebhookSigner;

        $timestamp = now()->getTimestamp();
        $signature = $signer->sign('{"amount":100}', 'whsec_test', $timestamp);

        $this->assertFalse(
            $signer->verify('{"amount":9999}', $signature, 'whsec_test', $timestamp)
        );
    }

    #[Test]
    public function a_captured_request_cannot_be_replayed_later(): void
    {
        $signer = new WebhookSigner;

        $payload = '{"id":"evt_1"}';
        $oldTimestamp = now()->subHour()->getTimestamp();
        $signature = $signer->sign($payload, 'whsec_test', $oldTimestamp);

        // The signature is genuine, but the timestamp is outside tolerance.
        $this->assertFalse(
            $signer->verify($payload, $signature, 'whsec_test', $oldTimestamp)
        );
    }

    #[Test]
    public function a_wrong_secret_fails_verification(): void
    {
        $signer = new WebhookSigner;

        $timestamp = now()->getTimestamp();
        $signature = $signer->sign('{"id":"evt_1"}', 'whsec_correct', $timestamp);

        $this->assertFalse(
            $signer->verify('{"id":"evt_1"}', $signature, 'whsec_wrong', $timestamp)
        );
    }

    #[Test]
    public function an_event_is_queued_only_for_endpoints_that_subscribed_to_it(): void
    {
        Http::fake();

        $subscribed = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
            'events' => [WebhookEvent::OrderDelivered->value],
        ]);

        $uninterested = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
            'events' => [WebhookEvent::OrderCancelled->value],
        ]);

        app(WebhookDispatcher::class)->dispatch(
            WebhookEvent::OrderDelivered,
            $this->business,
            ['id' => 'del_test'],
        );

        $this->assertDatabaseHas('webhook_deliveries', ['webhook_endpoint_id' => $subscribed->id]);
        $this->assertDatabaseMissing('webhook_deliveries', ['webhook_endpoint_id' => $uninterested->id]);
    }

    #[Test]
    public function the_same_event_is_never_queued_twice_for_one_endpoint(): void
    {
        Http::fake();

        $endpoint = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $dispatcher = app(WebhookDispatcher::class);

        // A retried listener re-raises the same domain event.
        $dispatcher->dispatch(WebhookEvent::OrderDelivered, $this->business, [], 'evt_fixed');
        $dispatcher->dispatch(WebhookEvent::OrderDelivered, $this->business, [], 'evt_fixed');

        $this->assertSame(
            1,
            WebhookDelivery::query()->where('webhook_endpoint_id', $endpoint->id)->count(),
        );
    }

    #[Test]
    public function a_delivered_webhook_is_marked_and_clears_the_failure_count(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $endpoint = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $endpoint->forceFill(['consecutive_failures' => 4])->save();

        $delivery = WebhookDelivery::factory()->create(['webhook_endpoint_id' => $endpoint->id]);

        app(SendWebhookJob::class, ['webhookDeliveryId' => $delivery->id])
            ->handle(app(WebhookSigner::class));

        $delivery->refresh();

        $this->assertSame(WebhookDeliveryStatus::Delivered, $delivery->status);
        $this->assertSame(200, $delivery->response_status);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertSame(0, $endpoint->fresh()->consecutive_failures);
    }

    #[Test]
    public function the_request_carries_the_signature_and_event_headers(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $endpoint = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => WebhookEvent::OrderPickedUp,
        ]);

        app(SendWebhookJob::class, ['webhookDeliveryId' => $delivery->id])
            ->handle(app(WebhookSigner::class));

        Http::assertSent(function ($request) use ($endpoint) {
            $signature = $request->header(config('platform.webhooks.signature_header'))[0] ?? null;
            $timestamp = $request->header(config('platform.webhooks.timestamp_header'))[0] ?? null;

            // The receiver must be able to verify what it was actually sent.
            return $signature !== null
                && $timestamp !== null
                && $request->header('X-Banha-Event')[0] === 'order.picked_up'
                && (new WebhookSigner)->verify(
                    $request->body(),
                    $signature,
                    $endpoint->secret,
                    (int) $timestamp,
                );
        });
    }

    #[Test]
    public function a_failing_endpoint_is_retried_with_a_backoff(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $endpoint = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $delivery = WebhookDelivery::factory()->create(['webhook_endpoint_id' => $endpoint->id]);

        app(SendWebhookJob::class, ['webhookDeliveryId' => $delivery->id])
            ->handle(app(WebhookSigner::class));

        $delivery->refresh();

        $this->assertSame(WebhookDeliveryStatus::Pending, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->next_attempt_at);
        $this->assertTrue($delivery->next_attempt_at->isFuture());
        $this->assertSame(1, $endpoint->fresh()->consecutive_failures);
    }

    #[Test]
    public function retries_eventually_stop_rather_than_hammering_a_dead_endpoint(): void
    {
        Http::fake(['*' => Http::response('gone', 410)]);

        $endpoint = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'attempts' => config('platform.webhooks.max_attempts') - 1,
        ]);

        app(SendWebhookJob::class, ['webhookDeliveryId' => $delivery->id])
            ->handle(app(WebhookSigner::class));

        $delivery->refresh();

        $this->assertSame(WebhookDeliveryStatus::Failed, $delivery->status);
        $this->assertNull($delivery->next_attempt_at);
    }

    #[Test]
    public function a_disabled_endpoint_is_abandoned_rather_than_called(): void
    {
        Http::fake();

        $endpoint = WebhookEndpoint::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $endpoint->forceFill(['disabled_at' => now()])->save();

        $delivery = WebhookDelivery::factory()->create(['webhook_endpoint_id' => $endpoint->id]);

        app(SendWebhookJob::class, ['webhookDeliveryId' => $delivery->id])
            ->handle(app(WebhookSigner::class));

        $this->assertSame(WebhookDeliveryStatus::Abandoned, $delivery->fresh()->status);

        Http::assertNothingSent();
    }

    #[Test]
    public function an_endpoint_can_be_registered_through_the_api_and_returns_its_secret_once(): void
    {
        $client = ApiClient::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $key = ApiKey::issue($client, 'Test')['plain_text'];

        $response = $this->withToken($key)->postJson('/api/v1/webhooks', [
            'url' => 'https://shop.example.com/hooks/banha',
            'events' => ['order.delivered', 'order.cancelled'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.object', 'webhook_endpoint')
            ->assertJsonPath('data.url', 'https://shop.example.com/hooks/banha');

        $this->assertStringStartsWith('whsec_', $response->json('data.secret'));

        // Listing it again must not hand the secret back out.
        $this->withToken($key)->getJson('/api/v1/webhooks')
            ->assertOk()
            ->assertJsonMissingPath('data.0.secret');
    }

    #[Test]
    public function a_webhook_url_pointing_at_a_private_address_is_rejected(): void
    {
        $client = ApiClient::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $key = ApiKey::issue($client, 'Test')['plain_text'];

        // Registering an internal address would turn the platform into a
        // probe of its own network.
        $this->withToken($key)->postJson('/api/v1/webhooks', [
            'url' => 'https://127.0.0.1/hooks',
            'events' => ['order.delivered'],
        ])->assertStatus(422);

        $this->withToken($key)->postJson('/api/v1/webhooks', [
            'url' => 'http://shop.example.com/hooks',
            'events' => ['order.delivered'],
        ])->assertStatus(422);
    }

    #[Test]
    public function a_business_cannot_touch_another_businesss_endpoint(): void
    {
        $mine = ApiClient::factory()->create([
            'owner_type' => $this->business->getMorphClass(),
            'owner_id' => $this->business->id,
        ]);

        $rival = Business::factory()->create();

        $rivalEndpoint = WebhookEndpoint::factory()->create([
            'owner_type' => $rival->getMorphClass(),
            'owner_id' => $rival->id,
        ]);

        $this->withToken(ApiKey::issue($mine, 'Test')['plain_text'])
            ->deleteJson('/api/v1/webhooks/'.$rivalEndpoint->id)
            ->assertNotFound();

        $this->assertDatabaseHas('webhook_endpoints', ['id' => $rivalEndpoint->id]);
    }
}
