<?php

namespace Tests\Unit;

use App\Domain\Dispatch\DispatchService;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingEngine;
use App\Enums\DeliveryStatus;
use App\Enums\PackageSize;
use App\Enums\VehicleType;
use App\Models\Business;
use App\Models\BusinessCompanyPreference;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\DeliveryOffer;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Rider;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The matching engine decides who gets offered work, which makes it the most
 * commercially sensitive component in the platform.
 *
 * These tests separate the two things it does: hard eligibility, where being
 * excluded must be non-negotiable, and ranking, where the order has to follow
 * from the configured weights.
 */
class MatchingEngineTest extends TestCase
{
    use RefreshDatabase;

    private Zone $pickupZone;

    private Zone $dropoffZone;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pickupZone = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        $this->dropoffZone = Zone::factory()->at(30.4560, 31.1900)->create(['code' => 'MNS']);
        $this->business = Business::factory()->create();

        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 2000]);
    }

    #[Test]
    public function it_ranks_a_company_with_an_available_rider(): void
    {
        $company = $this->companyServing(['CTR', 'MNS']);
        Rider::factory()->for($company)->online(30.4612, 31.1842)->create();

        $candidates = $this->rank();

        $this->assertCount(1, $candidates);
        $this->assertSame($company->id, $candidates->first()->company->id);
    }

    #[Test]
    public function a_company_with_no_online_rider_is_not_a_candidate(): void
    {
        $company = $this->companyServing(['CTR', 'MNS']);
        Rider::factory()->for($company)->create(); // offline

        $this->assertTrue($this->rank()->isEmpty());
    }

    #[Test]
    public function a_rider_already_at_capacity_does_not_make_their_company_eligible(): void
    {
        $company = $this->companyServing(['CTR', 'MNS']);
        Rider::factory()->for($company)->online()->atCapacity()->create();

        $this->assertTrue($this->rank()->isEmpty());
    }

    #[Test]
    public function a_suspended_company_is_never_offered_work(): void
    {
        $company = $this->companyServing(['CTR', 'MNS'], fn () => DeliveryCompany::factory()->suspended());
        Rider::factory()->for($company)->online()->create();

        $this->assertTrue($this->rank()->isEmpty());
    }

    #[Test]
    public function a_company_that_does_not_serve_the_dropoff_zone_is_excluded(): void
    {
        // Collects from the centre, but will not deliver to Manshia.
        $company = $this->companyServing(['CTR']);
        Rider::factory()->for($company)->online()->create();

        $this->assertTrue($this->rank()->isEmpty());
    }

    #[Test]
    public function a_company_with_no_declared_service_area_is_treated_as_city_wide(): void
    {
        // A newly onboarded partner has not drawn its map yet; freezing it out
        // would make onboarding impossible.
        $company = DeliveryCompany::factory()->create();
        Rider::factory()->for($company)->online()->create();

        $this->assertCount(1, $this->rank());
    }

    #[Test]
    public function a_parcel_too_large_for_the_available_vehicles_finds_no_candidate(): void
    {
        $company = $this->companyServing(['CTR', 'MNS']);
        Rider::factory()->for($company)->online()->vehicle(VehicleType::Motorcycle)->create();

        // A motorcycle tops out at a medium parcel.
        $this->assertTrue($this->rank(packageSize: PackageSize::Bulky)->isEmpty());
    }

    #[Test]
    public function a_van_can_carry_what_a_motorcycle_cannot(): void
    {
        $company = $this->companyServing(['CTR', 'MNS']);
        Rider::factory()->for($company)->online()->vehicle(VehicleType::Van)->create();

        $this->assertCount(1, $this->rank(packageSize: PackageSize::Bulky));
    }

    #[Test]
    public function a_company_blocked_by_the_business_is_excluded(): void
    {
        $blocked = $this->companyServing(['CTR', 'MNS']);
        $allowed = $this->companyServing(['CTR', 'MNS']);

        Rider::factory()->for($blocked)->online()->create();
        Rider::factory()->for($allowed)->online()->create();

        BusinessCompanyPreference::create([
            'business_id' => $this->business->id,
            'delivery_company_id' => $blocked->id,
            'preference' => BusinessCompanyPreference::BLOCKED,
        ]);

        $candidates = $this->rank();

        $this->assertCount(1, $candidates);
        $this->assertSame($allowed->id, $candidates->first()->company->id);
    }

    #[Test]
    public function a_company_already_offered_this_delivery_is_not_offered_again(): void
    {
        $first = $this->companyServing(['CTR', 'MNS']);
        $second = $this->companyServing(['CTR', 'MNS']);

        Rider::factory()->for($first)->online()->create();
        Rider::factory()->for($second)->online()->create();

        $delivery = $this->delivery();

        DeliveryOffer::factory()->create([
            'delivery_id' => $delivery->id,
            'delivery_company_id' => $first->id,
        ]);

        $context = app(DispatchService::class)->buildContext($delivery->fresh());
        $candidates = app(MatchingEngine::class)->rank($context);

        $this->assertCount(1, $candidates);
        $this->assertSame($second->id, $candidates->first()->company->id);
    }

    #[Test]
    public function a_company_at_its_concurrency_ceiling_is_excluded(): void
    {
        $company = $this->companyServing(['CTR', 'MNS']);
        $company->update(['max_concurrent_deliveries' => 1]);

        Rider::factory()->for($company)->online()->create();

        // One delivery already in flight fills the company's only slot.
        Delivery::factory()->create([
            'delivery_company_id' => $company->id,
            'status' => DeliveryStatus::InTransit,
        ]);

        $this->assertTrue($this->rank()->isEmpty());
    }

    #[Test]
    public function a_closed_company_is_excluded_by_its_working_hours(): void
    {
        $company = $this->companyServing(['CTR', 'MNS']);

        $company->update([
            'working_hours' => collect([
                'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday',
            ])->mapWithKeys(fn (string $day) => [$day => ['closed' => true]])->all(),
        ]);

        Rider::factory()->for($company)->online()->create();

        $this->assertTrue($this->rank()->isEmpty());
    }

    #[Test]
    public function the_nearer_companys_rider_scores_higher_on_distance(): void
    {
        $near = $this->companyServing(['CTR', 'MNS']);
        $far = $this->companyServing(['CTR', 'MNS']);

        // ~50 m from the pickup versus ~5 km away.
        Rider::factory()->for($near)->online(30.4614, 31.1841)->create();
        Rider::factory()->for($far)->online(30.5060, 31.1840)->create();

        $candidates = $this->rank()->keyBy(fn (MatchCandidate $c) => $c->company->id);

        $this->assertGreaterThan(
            $candidates[$far->id]->scores['distance'],
            $candidates[$near->id]->scores['distance'],
        );
    }

    #[Test]
    public function a_cheaper_company_wins_under_the_cheapest_strategy(): void
    {
        $this->business->update(['matching_strategy' => 'cheapest']);

        $cheap = $this->companyServing(['CTR', 'MNS']);
        $dear = $this->companyServing(['CTR', 'MNS']);

        Rider::factory()->for($cheap)->online()->create();
        Rider::factory()->for($dear)->online()->create();

        PricingRule::factory()->forCompany($cheap->id)->create(['name' => 'Cheap base', 'amount_minor' => 1200]);
        PricingRule::factory()->forCompany($dear->id)->create(['name' => 'Dear base', 'amount_minor' => 4000]);

        $candidates = $this->rank();

        $this->assertSame($cheap->id, $candidates->first()->company->id);
    }

    #[Test]
    public function a_preferred_company_is_ranked_ahead_of_its_rivals(): void
    {
        $ordinary = $this->companyServing(['CTR', 'MNS']);
        $preferred = $this->companyServing(['CTR', 'MNS']);

        // The preferred company is deliberately the worse match on distance,
        // so only the preference can explain it winning.
        Rider::factory()->for($ordinary)->online(30.4612, 31.1841)->create();
        Rider::factory()->for($preferred)->online(30.4900, 31.1840)->create();

        BusinessCompanyPreference::create([
            'business_id' => $this->business->id,
            'delivery_company_id' => $preferred->id,
            'preference' => BusinessCompanyPreference::PREFERRED,
        ]);

        $this->assertSame($preferred->id, $this->rank()->first()->company->id);
    }

    #[Test]
    public function a_brand_new_company_is_not_frozen_out_by_a_cold_start(): void
    {
        $established = $this->companyServing(['CTR', 'MNS']);
        $newcomer = $this->companyServing(
            ['CTR', 'MNS'],
            fn () => DeliveryCompany::factory()->newlyOnboarded(),
        );

        Rider::factory()->for($established)->online(30.4612, 31.1841)->create();
        Rider::factory()->for($newcomer)->online(30.4612, 31.1841)->create();

        $candidates = $this->rank()->keyBy(fn (MatchCandidate $c) => $c->company->id);

        // With no history it must still score on reliability, or it could
        // never earn the history that would let it compete.
        $this->assertGreaterThan(0.0, $candidates[$newcomer->id]->scores['reliability']);
        $this->assertGreaterThan(0.0, $candidates[$newcomer->id]->scores['acceptance_rate']);
    }

    /**
     * @param  array<int, string>  $zoneCodes
     */
    private function companyServing(array $zoneCodes, ?callable $factory = null): DeliveryCompany
    {
        $company = $factory ? $factory()->create() : DeliveryCompany::factory()->create();

        $zones = Zone::query()->whereIn('code', $zoneCodes)->pluck('id');

        $company->serviceAreas()->sync(
            $zones->mapWithKeys(fn (string $id) => [$id => [
                'accepts_pickup' => true,
                'accepts_dropoff' => true,
                'surcharge_minor' => 0,
            ]])->all()
        );

        return $company->fresh();
    }

    private function delivery(PackageSize $packageSize = PackageSize::Small): Delivery
    {
        $order = Order::factory()
            ->for($this->business)
            ->between($this->pickupZone, $this->dropoffZone)
            ->size($packageSize)
            ->create();

        return Delivery::factory()->create([
            'order_id' => $order->id,
            'business_id' => $this->business->id,
            'distance_meters' => 1400,
        ]);
    }

    /**
     * @return Collection<int, MatchCandidate>
     */
    private function rank(PackageSize $packageSize = PackageSize::Small): Collection
    {
        $delivery = $this->delivery($packageSize);
        $context = app(DispatchService::class)->buildContext($delivery);

        return app(MatchingEngine::class)->rank($context);
    }
}
