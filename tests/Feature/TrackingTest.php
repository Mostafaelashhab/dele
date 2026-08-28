<?php

namespace Tests\Feature;

use App\Domain\Tracking\TrackingPresenter;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\DeliveryLocation;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The tracking page is unauthenticated, so the token in the URL is the only
 * thing between a stranger and a customer's address.
 *
 * These tests are mostly about what must *not* appear on it.
 */
class TrackingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_valid_token_shows_the_delivery_status(): void
    {
        $delivery = $this->delivery(DeliveryStatus::InTransit);

        $this->get(route('tracking.show', $delivery->tracking_token))
            ->assertOk()
            ->assertSee($delivery->status->label())
            ->assertSee($delivery->business->displayName());
    }

    #[Test]
    public function an_unknown_token_reveals_nothing(): void
    {
        $this->get(route('tracking.show', str_repeat('a', 56)))
            ->assertOk()
            ->assertSee(__('app.tracking.not_found'));
    }

    #[Test]
    public function the_token_is_long_and_unpredictable(): void
    {
        $tokens = Delivery::factory()->count(25)->create()->pluck('tracking_token');

        // Guessing must be impractical, and no two deliveries may collide.
        $this->assertCount(25, $tokens->unique());

        foreach ($tokens as $token) {
            $this->assertGreaterThanOrEqual(48, mb_strlen($token));
            $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $token);
        }
    }

    #[Test]
    public function the_page_never_exposes_the_price_or_the_riders_phone_number(): void
    {
        $delivery = $this->delivery(DeliveryStatus::InTransit);

        $delivery->update(['price_minor' => 12345]);

        $response = $this->get(route('tracking.show', $delivery->tracking_token))->assertOk();

        $response->assertDontSee($delivery->rider->phone);
        $response->assertDontSee('123.45');
        $response->assertDontSee($delivery->id);
        $response->assertDontSee($delivery->order->pickupSnapshot()->contactPhone);
    }

    #[Test]
    public function the_page_is_never_indexed_and_leaks_no_referrer(): void
    {
        $delivery = $this->delivery();

        $this->get(route('tracking.show', $delivery->tracking_token))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow, noarchive"', false)
            ->assertSee('name="referrer" content="no-referrer"', false);
    }

    #[Test]
    public function only_the_riders_first_name_is_shown(): void
    {
        $delivery = $this->delivery(DeliveryStatus::InTransit);
        $delivery->rider->update(['name' => 'محمد إبراهيم السيد']);

        $presented = app(TrackingPresenter::class)->present($delivery->fresh());

        $this->assertSame('محمد', $presented['rider']['first_name']);
        $this->assertArrayNotHasKey('phone', $presented['rider']);
    }

    #[Test]
    public function the_riders_position_is_hidden_until_the_parcel_is_collected(): void
    {
        $delivery = $this->delivery(DeliveryStatus::Assigned);

        DeliveryLocation::factory()->create([
            'rider_id' => $delivery->rider_id,
            'delivery_id' => $delivery->id,
        ]);

        $presented = app(TrackingPresenter::class)->present($delivery->fresh());

        // Before pickup the rider's whereabouts are nobody's business.
        $this->assertNull($presented['rider_position']);
    }

    #[Test]
    public function the_riders_position_appears_while_the_parcel_is_in_transit(): void
    {
        $delivery = $this->delivery(DeliveryStatus::InTransit);

        DeliveryLocation::factory()->create([
            'rider_id' => $delivery->rider_id,
            'delivery_id' => $delivery->id,
            'latitude' => 30.4571,
            'longitude' => 31.1888,
        ]);

        $presented = app(TrackingPresenter::class)->present($delivery->fresh());

        $this->assertSame(30.4571, $presented['rider_position']['lat']);
        $this->assertSame(31.1888, $presented['rider_position']['lng']);
    }

    #[Test]
    public function the_riders_position_is_hidden_again_once_delivered(): void
    {
        $delivery = $this->delivery(DeliveryStatus::Delivered);

        DeliveryLocation::factory()->create([
            'rider_id' => $delivery->rider_id,
            'delivery_id' => $delivery->id,
        ]);

        $presented = app(TrackingPresenter::class)->present($delivery->fresh());

        $this->assertNull($presented['rider_position']);
        $this->assertTrue($presented['is_complete']);
    }

    #[Test]
    public function the_json_tracking_endpoint_needs_no_api_key(): void
    {
        $delivery = $this->delivery(DeliveryStatus::PickedUp);

        $this->getJson('/api/v1/tracking/'.$delivery->tracking_token)
            ->assertOk()
            ->assertJsonPath('data.status', 'picked_up')
            ->assertJsonStructure([
                'data' => ['order_number', 'status', 'status_label', 'timeline_step', 'business', 'timeline'],
            ]);
    }

    #[Test]
    public function the_json_tracking_endpoint_404s_on_a_bad_token(): void
    {
        $this->getJson('/api/v1/tracking/'.str_repeat('b', 56))
            ->assertNotFound()
            ->assertJsonPath('error.type', 'not_found');
    }

    private function delivery(DeliveryStatus $status = DeliveryStatus::Accepted): Delivery
    {
        $company = DeliveryCompany::factory()->create();
        $rider = Rider::factory()->for($company)->online()->create();
        $order = Order::factory()->create();

        return Delivery::factory()->create([
            'order_id' => $order->id,
            'business_id' => $order->business_id,
            'delivery_company_id' => $company->id,
            'rider_id' => $rider->id,
            'status' => $status,
            'delivered_at' => $status === DeliveryStatus::Delivered ? now() : null,
        ]);
    }
}
