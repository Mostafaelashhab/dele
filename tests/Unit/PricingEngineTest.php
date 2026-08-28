<?php

namespace Tests\Unit;

use App\Domain\Pricing\PriceLine;
use App\Domain\Pricing\PriceQuote;
use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Enums\PricingComponent;
use App\Enums\PricingRuleType;
use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\PricingRule;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The pricing engine decides what a business pays and what a company earns, so
 * every rule that can move a number is pinned down here.
 *
 * The invariant that matters most: a quote's lines always sum to its total. If
 * that ever stops holding, a price becomes unexplainable.
 */
class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private PricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(PricingEngine::class);

        // Config supplies fallbacks so a fresh install prices sanely before
        // any rule is seeded. Those fallbacks are neutralised here so each
        // test's own rules are the only thing moving the number, and the
        // fallback behaviour gets its own dedicated test below.
        config([
            'platform.pricing.default_base_minor' => 0,
            'platform.pricing.default_per_km_minor' => 0,
            'platform.pricing.free_distance_meters' => 0,
            'platform.pricing.minimum_fare_minor' => 0,
            'platform.pricing.rounding_increment_minor' => 0,
            'platform.pricing.platform_fee.percentage_bps' => 1200,
            'platform.pricing.platform_fee.fixed_minor' => 0,
            'platform.pricing.platform_fee.minimum_minor' => 0,
        ]);
    }

    #[Test]
    public function it_falls_back_to_configured_defaults_when_no_rules_exist(): void
    {
        config([
            'platform.pricing.default_base_minor' => 1500,
            'platform.pricing.default_per_km_minor' => 300,
            'platform.pricing.free_distance_meters' => 1500,
        ]);

        // No pricing_rules rows at all: a brand new installation must still
        // produce a usable price rather than quoting zero.
        $quote = $this->engine->quote($this->context(distanceMeters: 4500));

        $this->assertSame(1500, $this->lineAmount($quote, PricingComponent::Base));
        $this->assertSame(900, $this->lineAmount($quote, PricingComponent::Distance));
        $this->assertSame(2400, $quote->total->minor);
    }

    #[Test]
    public function it_rounds_the_total_up_to_the_configured_cash_increment(): void
    {
        config(['platform.pricing.rounding_increment_minor' => 50]);

        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1730]);

        $quote = $this->engine->quote($this->context());

        $this->assertSame(20, $this->lineAmount($quote, PricingComponent::Rounding));
        $this->assertSame(1750, $quote->total->minor);
    }

    #[Test]
    public function it_charges_a_base_fare_plus_distance_beyond_the_free_allowance(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);
        PricingRule::factory()->perKilometre(300, freeMeters: 1500)->create(['name' => 'Distance']);

        // 4.5 km with the first 1.5 km included leaves 3 km chargeable.
        $quote = $this->engine->quote($this->context(distanceMeters: 4500));

        $this->assertSame(1500, $this->lineAmount($quote, PricingComponent::Base));
        $this->assertSame(900, $this->lineAmount($quote, PricingComponent::Distance));
        $this->assertSame(2400, $quote->total->minor);
    }

    #[Test]
    public function distance_is_charged_per_started_kilometre(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);
        PricingRule::factory()->perKilometre(300, freeMeters: 1000)->create(['name' => 'Distance']);

        // 2 100 m leaves 1 100 chargeable metres, which is two started km —
        // couriers here quote whole kilometres, not fractions.
        $quote = $this->engine->quote($this->context(distanceMeters: 2100));

        $this->assertSame(600, $this->lineAmount($quote, PricingComponent::Distance));
    }

    #[Test]
    public function a_zone_pair_flat_rate_replaces_base_and_distance(): void
    {
        $pickup = Zone::factory()->create();
        $dropoff = Zone::factory()->create();

        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);
        PricingRule::factory()->perKilometre(300)->create(['name' => 'Distance']);

        PricingRule::factory()->ofType(PricingRuleType::ZonePairFlat)->create([
            'name' => 'Centre to Manshia',
            'pickup_zone_id' => $pickup->id,
            'dropoff_zone_id' => $dropoff->id,
            'amount_minor' => 2000,
        ]);

        $quote = $this->engine->quote($this->context(
            distanceMeters: 9000,
            pickupZone: $pickup,
            dropoffZone: $dropoff,
        ));

        // A flat rate is the whole price for the leg: distance must not stack
        // on top of it, however far the trip turns out to be.
        $this->assertNull($this->line($quote, PricingComponent::Base));
        $this->assertNull($this->line($quote, PricingComponent::Distance));
        $this->assertSame(2000, $quote->total->minor);
    }

    #[Test]
    public function express_priority_applies_a_percentage_surcharge(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 2000]);

        PricingRule::factory()->ofType(PricingRuleType::PrioritySurcharge)->create([
            'name' => 'Express',
            'priority' => DeliveryPriority::Express,
            'percentage_bps' => 3000,
            'amount_minor' => 0,
        ]);

        $standard = $this->engine->quote($this->context(priority: DeliveryPriority::Standard));
        $express = $this->engine->quote($this->context(priority: DeliveryPriority::Express));

        $this->assertSame(2000, $standard->total->minor);
        $this->assertSame(600, $this->lineAmount($express, PricingComponent::PrioritySurcharge));
        $this->assertSame(2600, $express->total->minor);
    }

    #[Test]
    public function the_minimum_fare_tops_up_a_short_trip(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 500]);
        PricingRule::factory()->ofType(PricingRuleType::MinimumFare)->create([
            'name' => 'Minimum',
            'amount_minor' => 1500,
        ]);

        $quote = $this->engine->quote($this->context(distanceMeters: 300));

        $this->assertSame(1000, $this->lineAmount($quote, PricingComponent::MinimumFareAdjustment));
        $this->assertSame(1500, $quote->total->minor);
    }

    #[Test]
    public function cash_on_delivery_is_charged_as_a_percentage_of_the_collected_amount(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 2000]);
        PricingRule::factory()->ofType(PricingRuleType::CodHandling)->create([
            'name' => 'Cash handling',
            'percentage_bps' => 100,
            'amount_minor' => 0,
        ]);

        $quote = $this->engine->quote($this->context(
            paymentType: PaymentType::CashOnDelivery,
            codAmount: Money::ofMinor(50000),
        ));

        $this->assertSame(500, $this->lineAmount($quote, PricingComponent::CodHandling));
    }

    #[Test]
    public function a_prepaid_order_is_never_charged_cash_handling(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 2000]);
        PricingRule::factory()->ofType(PricingRuleType::CodHandling)->create([
            'name' => 'Cash handling',
            'percentage_bps' => 100,
        ]);

        $quote = $this->engine->quote($this->context(codAmount: Money::ofMinor(50000)));

        $this->assertNull($this->line($quote, PricingComponent::CodHandling));
    }

    #[Test]
    public function a_company_rule_overrides_the_platform_default_of_the_same_type(): void
    {
        $company = DeliveryCompany::factory()->create();

        PricingRule::factory()->create(['name' => 'Platform base', 'amount_minor' => 1500]);
        PricingRule::factory()->forCompany($company->id)->create([
            'name' => 'Company base',
            'amount_minor' => 2200,
        ]);

        $platformQuote = $this->engine->quote($this->context());
        $companyQuote = $this->engine->quote($this->context(company: $company));

        $this->assertSame(1500, $platformQuote->total->minor);
        $this->assertSame(2200, $companyQuote->total->minor);
    }

    #[Test]
    public function the_total_always_equals_the_sum_of_its_lines(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);
        PricingRule::factory()->perKilometre(275, freeMeters: 900)->create(['name' => 'Distance']);
        PricingRule::factory()->ofType(PricingRuleType::MinimumFare)->create([
            'name' => 'Minimum',
            'amount_minor' => 1500,
        ]);

        foreach ([300, 1200, 3300, 7800, 15000] as $distance) {
            $quote = $this->engine->quote($this->context(distanceMeters: $distance));

            $summed = $quote->lines->reduce(
                fn (int $carry, $line) => $carry + $line->amount->minor,
                0,
            );

            $this->assertSame(
                $quote->total->minor,
                $summed,
                "Lines did not sum to the total at {$distance}m.",
            );
        }
    }

    #[Test]
    public function the_platform_fee_and_company_payout_split_the_total_exactly(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 2500]);

        $quote = $this->engine->quote($this->context());

        // Not a penny may be created or lost in the split.
        $this->assertSame(
            $quote->total->minor,
            $quote->platformFee->minor + $quote->companyPayout->minor,
        );

        $this->assertSame(300, $quote->platformFee->minor);
        $this->assertSame(2200, $quote->companyPayout->minor);
        $this->assertGreaterThan($quote->companyPayout->minor, $quote->total->minor);
    }

    #[Test]
    public function a_business_with_a_negotiated_rate_pays_its_own_platform_fee(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 10000]);

        $standard = Business::factory()->create(['platform_fee_bps' => null]);
        $negotiated = Business::factory()->create(['platform_fee_bps' => 500]);

        $standardFee = $this->engine->quote($this->context(business: $standard))->platformFee;
        $negotiatedFee = $this->engine->quote($this->context(business: $negotiated))->platformFee;

        $this->assertSame(500, $negotiatedFee->minor);
        $this->assertGreaterThan($negotiatedFee->minor, $standardFee->minor);
    }

    #[Test]
    public function an_inactive_rule_is_ignored(): void
    {
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);
        PricingRule::factory()->ofType(PricingRuleType::PackageSurcharge)->create([
            'name' => 'Retired surcharge',
            'package_size' => PackageSize::Small,
            'amount_minor' => 900,
            'is_active' => false,
        ]);

        $quote = $this->engine->quote($this->context());

        $this->assertNull($this->line($quote, PricingComponent::PackageSurcharge));
        $this->assertSame(1500, $quote->total->minor);
    }

    private function context(
        int $distanceMeters = 3000,
        DeliveryPriority $priority = DeliveryPriority::Standard,
        PackageSize $packageSize = PackageSize::Small,
        PaymentType $paymentType = PaymentType::Prepaid,
        ?Money $codAmount = null,
        ?Zone $pickupZone = null,
        ?Zone $dropoffZone = null,
        ?Business $business = null,
        ?DeliveryCompany $company = null,
    ): PricingContext {
        return new PricingContext(
            distanceMeters: $distanceMeters,
            estimatedMinutes: 25,
            priority: $priority,
            packageSize: $packageSize,
            paymentType: $paymentType,
            codAmount: $codAmount ?? Money::zero(),
            pickupZone: $pickupZone,
            dropoffZone: $dropoffZone,
            business: $business,
            deliveryCompany: $company,
        );
    }

    private function line(PriceQuote $quote, PricingComponent $component): ?PriceLine
    {
        return $quote->lines->first(fn ($line) => $line->component === $component);
    }

    private function lineAmount(PriceQuote $quote, PricingComponent $component): int
    {
        return $this->line($quote, $component)?->amount->minor ?? 0;
    }
}
