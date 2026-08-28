<?php

namespace Tests\Feature;

use App\Actions\Orders\CreateOrderAction;
use App\Domain\Dispatch\DueWorkSweeper;
use App\Domain\Orders\OrderData;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Enums\DeliveryStatus;
use App\Enums\OfferStatus;
use App\Enums\PaymentType;
use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\DeliveryOffer;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Rider;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dispatch with nothing ticking.
 *
 * This network runs with no cron entry and no queue worker: jobs execute
 * inline, and anything that becomes due with the passing of time is swept when
 * somebody next looks at the system. That is a real architectural constraint,
 * and these tests hold it — an order must reach the delivery companies during
 * the request that created it, and a timed-out offer must roll over without a
 * scheduler having run.
 */
class NoSchedulerDispatchTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private DeliveryCompany $first;

    private DeliveryCompany $second;

    private Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Cache::flush();

        $this->zone = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);

        $this->business = Business::factory()->create();

        // Two companies, so "the order reached more than the one nearest
        // company" is something this can actually assert.
        $this->first = $this->companyWithRider(30.4612, 31.1841);
        $this->second = $this->companyWithRider(30.4640, 31.1880);
    }

    /**
     * The behaviour the whole no-scheduler design exists to deliver.
     */
    #[Test]
    public function creating_an_order_puts_it_in_front_of_companies_immediately(): void
    {
        $this->assertSame('sync', config('queue.default'), 'This design depends on inline execution.');

        $order = $this->createOrder();
        $delivery = $order->fresh()->currentDelivery;

        $this->assertContains(
            $delivery->status,
            [DeliveryStatus::Searching, DeliveryStatus::Offered],
            'The delivery should already be looking for a company.'
        );

        $offers = DeliveryOffer::where('delivery_id', $delivery->id)->get();

        $this->assertNotEmpty(
            $offers,
            'No offer reached any company. With no worker running, dispatch must happen in-request.'
        );

        $this->assertTrue(
            $offers->every(fn (DeliveryOffer $offer) => $offer->status === OfferStatus::Pending),
            'A freshly sent offer should be waiting on an answer, not already closed.'
        );

        $this->assertDatabaseCount('jobs', 0);
    }

    /**
     * Offers are dispatched with a future deadline, and on the sync driver a
     * delayed job runs immediately. Without the guard, every offer would be
     * expired the instant it was created.
     */
    #[Test]
    public function an_offer_is_not_expired_the_moment_it_is_sent(): void
    {
        $order = $this->createOrder();

        $offer = DeliveryOffer::where('delivery_id', $order->fresh()->currentDelivery->id)->firstOrFail();

        $this->assertSame(OfferStatus::Pending, $offer->status);
        $this->assertTrue($offer->expires_at->isFuture(), 'The company must be given its full timeout.');
    }

    /**
     * The scheduler's job, done on read instead.
     */
    #[Test]
    public function an_unanswered_offer_rolls_over_without_a_scheduler(): void
    {
        $order = $this->createOrder();
        $delivery = $order->fresh()->currentDelivery;

        $offer = DeliveryOffer::where('delivery_id', $delivery->id)->firstOrFail();

        // Nobody answered, and the deadline passed while the app sat idle.
        $this->travelTo($offer->expires_at->copy()->addSeconds(5));

        app(DueWorkSweeper::class)->sweep(force: true);

        $this->assertNotSame(
            OfferStatus::Pending,
            $offer->fresh()->status,
            'A deadline that has passed must close the offer.'
        );
    }

    /**
     * A page load is what makes time pass in this design, so the sweep has to
     * actually run off ordinary traffic rather than only when called directly.
     */
    #[Test]
    public function an_ordinary_page_load_sweeps_due_work(): void
    {
        $order = $this->createOrder();
        $offer = DeliveryOffer::where('delivery_id', $order->fresh()->currentDelivery->id)->firstOrFail();

        $this->travelTo($offer->expires_at->copy()->addSeconds(5));
        Cache::flush();

        $this->get('/')->assertOk();

        $this->assertNotSame(
            OfferStatus::Pending,
            $offer->fresh()->status,
            'Traffic is the heartbeat here; a page load must move due work along.'
        );
    }

    /**
     * Without the throttle a polling board would sweep on every tick.
     */
    #[Test]
    public function the_sweep_is_throttled_so_traffic_does_not_run_it_every_time(): void
    {
        Cache::flush();

        $this->assertNotSame([], app(DueWorkSweeper::class)->sweep(), 'The first sweep should run.');
        $this->assertSame([], app(DueWorkSweeper::class)->sweep(), 'An immediate second sweep should be skipped.');
    }

    private function companyWithRider(float $lat, float $lng): DeliveryCompany
    {
        $company = DeliveryCompany::factory()->create(['working_hours' => null]);

        $company->serviceAreas()->sync([
            $this->zone->id => [
                'accepts_pickup' => true,
                'accepts_dropoff' => true,
                'surcharge_minor' => 0,
            ],
        ]);

        Rider::factory()->for($company)->online($lat, $lng)->create();

        return $company;
    }

    private function createOrder(): Order
    {
        return app(CreateOrderAction::class)->handle(
            business: $this->business,
            data: new OrderData(
                pickup: new LocationSnapshot(
                    contactName: 'المتجر',
                    contactPhone: '01000000001',
                    addressLine: 'وسط البلد',
                    area: $this->zone->name,
                    city: 'Banha',
                    latitude: 30.4610,
                    longitude: 31.1840,
                    zoneId: $this->zone->id,
                ),
                dropoff: new LocationSnapshot(
                    contactName: 'سارة محمود',
                    contactPhone: '01000000002',
                    addressLine: '١٢ شارع الجلاء',
                    area: $this->zone->name,
                    city: 'Banha',
                    latitude: 30.4560,
                    longitude: 31.1900,
                    zoneId: $this->zone->id,
                ),
                paymentType: PaymentType::Prepaid,
                reference: 'NOSCHED-'.fake()->unique()->numerify('####'),
            ),
        );
    }
}
