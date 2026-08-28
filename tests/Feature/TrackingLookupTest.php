<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The public order lookup.
 *
 * An order number is short and quotable by design, so this endpoint is the
 * one place where a guessable identifier could open somebody's address. The
 * second factor and the throttle are the whole point of it, and these tests
 * exist to keep them in place.
 */
class TrackingLookupTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    private Delivery $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('tracking-lookup:127.0.0.1');

        $pickup = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        $dropoff = Zone::factory()->at(30.4560, 31.1900)->create(['code' => 'MNS']);

        $this->order = Order::factory()
            ->for(Business::factory())
            ->between($pickup, $dropoff)
            ->create(['number' => 'BN260828-ABCDE']);

        // The recipient's number is the second factor.
        $this->order->forceFill([
            'dropoff' => array_merge(
                $this->order->dropoffSnapshot()->jsonSerialize(),
                ['contact_phone' => '01098765432'],
            ),
        ])->save();

        $this->delivery = Delivery::factory()->create([
            'order_id' => $this->order->id,
            'business_id' => $this->order->business_id,
        ]);
    }

    #[Test]
    public function the_right_number_and_phone_open_the_tracking_page(): void
    {
        $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-ABCDE',
            'phone' => '01098765432',
        ])->assertRedirect(route('tracking.show', ['token' => $this->delivery->tracking_token]));
    }

    #[Test]
    public function the_order_number_is_matched_case_insensitively(): void
    {
        // Shop owners read these off a receipt and type them however.
        $this->post(route('tracking.lookup'), [
            'number' => '  bn260828-abcde  ',
            'phone' => '01098765432',
        ])->assertRedirect(route('tracking.show', ['token' => $this->delivery->tracking_token]));
    }

    #[Test]
    public function a_phone_written_with_a_country_code_still_matches(): void
    {
        $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-ABCDE',
            'phone' => '+201098765432',
        ])->assertRedirect(route('tracking.show', ['token' => $this->delivery->tracking_token]));
    }

    #[Test]
    public function the_order_number_alone_is_not_enough(): void
    {
        // This is the whole reason the second factor exists: a guessed order
        // number must not reveal a customer's address.
        $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-ABCDE',
            'phone' => '01000000000',
        ])->assertSessionHasErrors('number');

        $this->assertGuest();
    }

    #[Test]
    public function an_unknown_order_and_a_wrong_phone_give_the_same_answer(): void
    {
        // Different messages would confirm which order numbers exist, so both
        // paths are asserted to produce the one indistinguishable message.
        $expected = __('tracking.lookup.not_found');

        $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-ZZZZZ',
            'phone' => '01098765432',
        ])->assertSessionHasErrors(['number' => $expected]);

        RateLimiter::clear('tracking-lookup:127.0.0.1');

        $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-ABCDE',
            'phone' => '01000000000',
        ])->assertSessionHasErrors(['number' => $expected]);
    }

    #[Test]
    public function repeated_guesses_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('tracking.lookup'), [
                'number' => 'BN260828-'.str_pad((string) $attempt, 5, 'X'),
                'phone' => '01098765432',
            ]);
        }

        $response = $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-ABCDE',
            'phone' => '01098765432',
        ]);

        // Even the correct details are refused once the limit is reached, so
        // grinding through the number space cannot pay off.
        $response->assertSessionHasErrors([
            'number' => __('tracking.lookup.throttled', ['minutes' => 5]),
        ]);
    }

    #[Test]
    public function a_successful_lookup_clears_the_throttle(): void
    {
        $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-WRONG',
            'phone' => '01098765432',
        ])->assertSessionHasErrors('number');

        $this->post(route('tracking.lookup'), [
            'number' => 'BN260828-ABCDE',
            'phone' => '01098765432',
        ])->assertRedirect();

        $this->assertSame(0, RateLimiter::attempts('tracking-lookup:127.0.0.1'));
    }

    #[Test]
    public function both_fields_are_required(): void
    {
        $this->post(route('tracking.lookup'), [])
            ->assertSessionHasErrors(['number', 'phone']);
    }

    #[Test]
    public function the_landing_page_offers_the_lookup(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('tracking.lookup.title'))
            ->assertSee(route('tracking.lookup'));
    }
}
