<?php

namespace Tests\Feature;

use App\Domain\Tracking\TrackingPresenter;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Asserts that the visual layer is actually present in the rendered output.
 *
 * A map or a chart that silently fails to render still returns a 200, so the
 * page-render tests alone cannot catch it. These assert on the markup those
 * components emit.
 */
class VisualMarkupTest extends TestCase
{
    use RefreshDatabase;

    private Delivery $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        $pickup = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        $dropoff = Zone::factory()->at(30.4560, 31.1900)->create(['code' => 'MNS']);

        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);

        $business = Business::factory()->create();
        $company = DeliveryCompany::factory()->create();
        $rider = Rider::factory()->for($company)->online(30.4600, 31.1860)->create();

        $order = Order::factory()->for($business)->between($pickup, $dropoff)->create();

        $this->delivery = Delivery::factory()->create([
            'order_id' => $order->id,
            'business_id' => $business->id,
            'delivery_company_id' => $company->id,
            'rider_id' => $rider->id,
            'status' => DeliveryStatus::InTransit,
        ]);

        // The tracking journey is built from the order's real events, so a
        // delivery conjured straight into InTransit has nothing to draw. A
        // real one collects these on its way through the state machine.
        foreach ([
            OrderEventType::OrderCreated,
            OrderEventType::DeliveryAccepted,
            OrderEventType::RiderAssigned,
            OrderEventType::OrderPickedUp,
            OrderEventType::DeliveryStarted,
        ] as $minute => $type) {
            $order->events()->create([
                'delivery_id' => $this->delivery->id,
                'type' => $type,
                'actor_type' => 'system',
                // Only customer-visible events reach the tracking page.
                'is_customer_visible' => true,
                'occurred_at' => now()->subMinutes(30 - $minute * 5),
            ]);
        }
    }

    #[Test]
    public function the_operations_board_renders_a_map_with_markers(): void
    {
        $response = $this->actingAs($this->platformAdmin())->get('/admin/live')->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('map-surface', $html, 'The map container is missing.');
        $this->assertStringContainsString('mapComponent()', $html, 'The map is not initialised.');

        // The payload must actually carry coordinates, not an empty config.
        // Blade's @js() escapes quotes as \u0022, so the markup is asserted in
        // exactly the form it ships in.
        $this->assertStringContainsString('\u0022lat\u0022', $html, 'The map payload has no coordinates.');
        $this->assertStringContainsString('\u0022markers\u0022:[{', $html, 'The map has no markers.');
        $this->assertStringContainsString('admin-live-ops', $html);
    }

    /**
     * The tracking page carries no map by product decision.
     *
     * A customer opening this link wants one answer — where is my parcel and
     * when does it arrive — and the journey, the status and the estimate give
     * it without a tile layer. This asserts the removal held rather than
     * leaving a half-initialised map container behind.
     */
    #[Test]
    public function the_tracking_page_shows_the_journey_rather_than_a_map(): void
    {
        $html = $this->get(route('tracking.show', $this->delivery->tracking_token))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('map-surface', $html);
        $this->assertStringNotContainsString('mapComponent()', $html);
        $this->assertStringNotContainsString('customer-tracking', $html);

        // What replaced it: the journey, built from the order's real events
        // rather than a fixed five-step summary.
        $this->assertStringContainsString('track-step', $html, 'The journey is missing.');
        $this->assertStringContainsString(OrderEventType::OrderPickedUp->label(), $html);
        $this->assertStringContainsString(OrderEventType::DeliveryStarted->label(), $html);

        // Each stage carries its own symbol rather than a repeated tick.
        $this->assertGreaterThan(
            3,
            substr_count($html, '<svg'),
            'The journey should draw a distinct symbol per stage.'
        );
    }

    /**
     * A customer sees where the rider is only while the rider has their
     * parcel — never before, never after.
     *
     * The map that used to prove this is gone, but the rule is not: the page
     * still reports when the rider's position was last updated. Asserted
     * against the payload rather than the markup, so the guarantee survives
     * whatever the page chooses to draw with it.
     */
    #[Test]
    public function the_customer_sees_the_rider_only_while_they_carry_the_parcel(): void
    {
        $presenter = app(TrackingPresenter::class);

        foreach ([DeliveryStatus::Assigned, DeliveryStatus::Delivered] as $hidden) {
            $this->delivery->forceFill(['status' => $hidden])->save();

            $this->assertNull(
                $presenter->present($this->delivery->fresh())['rider_position'],
                "The rider must not be visible while the delivery is {$hidden->value}."
            );
        }

        $this->delivery->forceFill(['status' => DeliveryStatus::InTransit])->save();

        $this->assertNotNull(
            $presenter->present($this->delivery->fresh())['rider_position'],
            'While carrying the parcel the rider position should be available.'
        );
    }

    #[Test]
    public function analytics_renders_charts_with_a_legend_and_a_table_twin(): void
    {
        $response = $this->actingAs($this->platformAdmin())->get('/admin/analytics')->assertOk();

        $html = $response->getContent();

        // The column chart, its legend, and the table view that backs it.
        $this->assertStringContainsString('var(--color-viz-series-1)', $html);
        $this->assertStringContainsString('var(--color-viz-critical)', $html);
        $this->assertStringContainsString('showTable', $html, 'The chart has no table twin.');

        // Meters expose their value to assistive technology.
        $this->assertStringContainsString('role="meter"', $html);
    }

    #[Test]
    public function the_zone_screen_draws_the_coverage_circles(): void
    {
        $html = $this->actingAs($this->platformAdmin())->get('/admin/zones')->assertOk()->getContent();

        $this->assertStringContainsString('map-surface', $html);
        $this->assertStringContainsString('\u0022radius\u0022', $html, 'Zone circles carry no radius.');
    }

    #[Test]
    public function the_rider_screen_offers_a_map_and_a_proof_photo_control(): void
    {
        $user = User::factory()->create();
        $this->delivery->rider->update(['user_id' => $user->id]);

        // The map is there throughout the journey.
        $html = $this->actingAs($user)
            ->get(route('rider.deliveries.show', $this->delivery->public_id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('map-surface', $html);

        // The proof control belongs to the hand-off step and appears only
        // once the rider is at the door — asking for it earlier would invite
        // a photo of the wrong place.
        $this->assertStringNotContainsString('imageUpload(', $html);

        $this->delivery->forceFill(['status' => DeliveryStatus::ArrivedAtDestination])->save();

        $html = $this->actingAs($user)
            ->get(route('rider.deliveries.show', $this->delivery->public_id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('imageUpload(', $html, 'No proof-of-delivery control.');
    }

    #[Test]
    public function empty_states_draw_an_illustration_rather_than_a_bare_message(): void
    {
        Delivery::query()->delete();
        Order::query()->delete();

        $html = $this->actingAs($this->platformAdmin())->get('/admin/live')->assertOk()->getContent();

        // The illustration component emits an inline svg with a ground ellipse.
        $this->assertStringContainsString('<ellipse', $html);
    }

    #[Test]
    public function business_categories_render_as_glyphs(): void
    {
        $html = $this->actingAs($this->platformAdmin())->get('/admin/businesses')->assertOk()->getContent();

        // The icon component emits stroke-based paths, not an emoji or a word.
        $this->assertStringContainsString('stroke="currentColor"', $html);
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->create();

        Role::where('slug', UserRole::PlatformAdmin->value)->first()->users()->attach($user->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
