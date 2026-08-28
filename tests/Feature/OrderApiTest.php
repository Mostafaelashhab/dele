<?php

namespace Tests\Feature;

use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use App\Models\ApiKey;
use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Rider;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The public API is a published contract, so these tests are as much about
 * the shape of what comes back as about the behaviour behind it.
 */
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $pickupZone = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        Zone::factory()->at(30.4560, 31.1900)->create(['code' => 'MNS']);

        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);
        PricingRule::factory()->perKilometre(300, freeMeters: 1000)->create(['name' => 'Distance']);

        $this->business = Business::factory()->create(['default_zone_id' => $pickupZone->id]);
        $this->apiKey = $this->issueKeyFor($this->business);

        $company = DeliveryCompany::factory()->create();
        Rider::factory()->for($company)->online(30.4612, 31.1841)->create();
    }

    #[Test]
    public function it_creates_a_delivery_and_returns_a_tracking_url(): void
    {
        $response = $this->withKey()->postJson('/api/v1/orders', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.object', 'order')
            ->assertJsonPath('data.reference', 'STORE-100293')
            ->assertJsonStructure([
                'data' => [
                    'id', 'object', 'reference', 'status', 'pickup', 'dropoff',
                    'price', 'tracking_url',
                    'delivery' => ['id', 'object', 'status', 'price', 'currency', 'tracking_url'],
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('data.price'));
        $this->assertStringContainsString('/track/', $response->json('data.tracking_url'));
        $this->assertStringStartsWith('del_', $response->json('data.delivery.id'));

        $this->assertDatabaseHas('orders', [
            'business_id' => $this->business->id,
            'reference' => 'STORE-100293',
        ]);
    }

    #[Test]
    public function it_rejects_a_request_with_no_api_key(): void
    {
        $this->postJson('/api/v1/orders', $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath('error.type', 'missing_api_key');
    }

    #[Test]
    public function it_rejects_an_invalid_api_key(): void
    {
        $this->withToken('bdn_notarealkey.andnotarealsecret')
            ->postJson('/api/v1/orders', $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath('error.type', 'invalid_api_key');
    }

    #[Test]
    public function a_revoked_key_stops_working_immediately(): void
    {
        ApiKey::query()->update(['revoked_at' => now()]);

        $this->withKey()->postJson('/api/v1/orders', $this->payload())
            ->assertUnauthorized();
    }

    #[Test]
    public function a_suspended_client_cannot_authenticate(): void
    {
        ApiClient::query()->update(['status' => ApiClientStatus::Suspended]);

        $this->withKey()->postJson('/api/v1/orders', $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath('error.type', 'client_suspended');
    }

    #[Test]
    public function it_validates_the_payload_and_names_the_offending_fields(): void
    {
        $response = $this->withKey()->postJson('/api/v1/orders', [
            'pickup' => ['name' => 'Store'],
            'dropoff' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.type', 'validation_failed')
            ->assertJsonStructure(['error' => ['type', 'message', 'fields']]);

        $this->assertArrayHasKey('pickup.phone', $response->json('error.fields'));
        $this->assertArrayHasKey('dropoff.name', $response->json('error.fields'));
    }

    #[Test]
    public function it_rejects_a_phone_number_that_is_not_an_egyptian_mobile(): void
    {
        $payload = $this->payload();
        $payload['dropoff']['phone'] = '12345';

        $this->withKey()->postJson('/api/v1/orders', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.type', 'validation_failed');
    }

    #[Test]
    public function it_requires_an_amount_when_the_order_is_cash_on_delivery(): void
    {
        $payload = $this->payload();
        $payload['payment_type'] = 'cod';

        $response = $this->withKey()->postJson('/api/v1/orders', $payload);

        $response->assertStatus(422);
        $this->assertArrayHasKey('cod_amount', $response->json('error.fields'));
    }

    #[Test]
    public function an_idempotency_key_makes_a_retry_safe(): void
    {
        $payload = $this->payload();

        $first = $this->withKey()
            ->withHeader('Idempotency-Key', 'pos-terminal-42')
            ->postJson('/api/v1/orders', $payload)
            ->assertCreated();

        // The shop's till timed out and sent the same request again.
        $second = $this->withKey()
            ->withHeader('Idempotency-Key', 'pos-terminal-42')
            ->postJson('/api/v1/orders', $payload)
            ->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame('true', $second->headers->get('Idempotent-Replay'));
        $this->assertSame(1, Order::query()->count());
    }

    #[Test]
    public function reusing_an_idempotency_key_with_a_different_body_is_rejected(): void
    {
        $this->withKey()
            ->withHeader('Idempotency-Key', 'pos-terminal-42')
            ->postJson('/api/v1/orders', $this->payload())
            ->assertCreated();

        $different = $this->payload();
        $different['reference'] = 'STORE-999999';

        // Silently returning the first order here would hide a real client bug.
        $this->withKey()
            ->withHeader('Idempotency-Key', 'pos-terminal-42')
            ->postJson('/api/v1/orders', $different)
            ->assertStatus(422)
            ->assertJsonPath('error.type', 'idempotency_key_reused');

        $this->assertSame(1, Order::query()->count());
    }

    #[Test]
    public function an_order_can_be_fetched_by_its_own_reference(): void
    {
        $this->withKey()->postJson('/api/v1/orders', $this->payload())->assertCreated();

        // Integrators usually only have their own reference to hand.
        $this->withKey()->getJson('/api/v1/orders/STORE-100293')
            ->assertOk()
            ->assertJsonPath('data.reference', 'STORE-100293');
    }

    #[Test]
    public function it_never_returns_another_businesss_order(): void
    {
        $mine = $this->withKey()->postJson('/api/v1/orders', $this->payload())->assertCreated();

        $rival = Business::factory()->create();
        $rivalKey = $this->issueKeyFor($rival);

        $this->withToken($rivalKey)
            ->getJson('/api/v1/orders/'.$mine->json('data.id'))
            ->assertNotFound()
            ->assertJsonPath('error.type', 'not_found');
    }

    #[Test]
    public function listing_orders_only_ever_shows_your_own(): void
    {
        $this->withKey()->postJson('/api/v1/orders', $this->payload())->assertCreated();

        $rival = Business::factory()->create();
        Order::factory()->for($rival)->count(3)->create();

        $response = $this->withKey()->getJson('/api/v1/orders')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('STORE-100293', $response->json('data.0.reference'));
    }

    #[Test]
    public function an_order_can_be_cancelled_through_the_api(): void
    {
        $created = $this->withKey()->postJson('/api/v1/orders', $this->payload())->assertCreated();

        $this->withKey()
            ->postJson('/api/v1/orders/'.$created->json('data.id').'/cancel', [
                'reason' => 'customer_changed_mind',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    #[Test]
    public function it_quotes_a_price_before_any_order_exists(): void
    {
        $response = $this->withKey()->postJson('/api/v1/quotes', [
            'pickup' => ['lat' => 30.4610, 'lng' => 31.1840],
            'dropoff' => ['lat' => 30.4560, 'lng' => 31.1900],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.object', 'quote')
            ->assertJsonStructure([
                'data' => ['price', 'currency', 'distance_meters', 'estimated_minutes', 'breakdown', 'expires_at'],
            ]);

        $this->assertGreaterThan(0, $response->json('data.price'));
        $this->assertSame('EGP', $response->json('data.currency'));
        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function it_exposes_the_zone_list(): void
    {
        $this->withKey()->getJson('/api/v1/zones')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'object', 'name', 'centre', 'base_price']]]);
    }

    #[Test]
    public function the_me_endpoint_identifies_the_calling_account(): void
    {
        $this->withKey()->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.object', 'api_client')
            ->assertJsonPath('data.owner.type', 'business')
            ->assertJsonPath('data.owner.name', $this->business->name)
            ->assertJsonPath('data.api_version', 'v1');
    }

    #[Test]
    public function every_response_carries_a_request_id_and_is_logged(): void
    {
        $response = $this->withKey()->getJson('/api/v1/me')->assertOk();

        $this->assertNotNull($response->headers->get('X-Request-Id'));
        $this->assertDatabaseHas('api_requests', [
            'path' => 'api/v1/me',
            'status_code' => 200,
        ]);
    }

    #[Test]
    public function delivery_events_are_readable_for_your_own_delivery(): void
    {
        $created = $this->withKey()->postJson('/api/v1/orders', $this->payload())->assertCreated();

        $this->withKey()
            ->getJson('/api/v1/deliveries/'.$created->json('data.delivery.id').'/events')
            ->assertOk()
            ->assertJsonStructure(['data' => [['object', 'type', 'label', 'occurred_at']]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'reference' => 'STORE-100293',
            'pickup' => [
                'name' => 'Store',
                'phone' => '01000000000',
                'address' => 'Banha',
                'lat' => 30.4610,
                'lng' => 31.1840,
            ],
            'dropoff' => [
                'name' => 'Customer',
                'phone' => '01000000000',
                'address' => 'Banha',
                'lat' => 30.4560,
                'lng' => 31.1900,
            ],
            'notes' => 'Call customer before arrival',
        ];
    }

    private function issueKeyFor(Business $business): string
    {
        $client = ApiClient::factory()->create([
            'owner_type' => $business->getMorphClass(),
            'owner_id' => $business->id,
        ]);

        return ApiKey::issue($client, 'Test')['plain_text'];
    }

    private function withKey(): self
    {
        return $this->withToken($this->apiKey);
    }
}
