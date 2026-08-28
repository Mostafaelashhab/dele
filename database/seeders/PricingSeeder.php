<?php

namespace Database\Seeders;

use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PricingRuleType;
use App\Models\PricingRule;
use Illuminate\Database\Seeder;

/**
 * The platform's default price list.
 *
 * These numbers are the starting point for the pilot and are meant to be
 * edited from the admin panel, not from code — which is exactly why they live
 * in a table rather than in a constant.
 */
class PricingSeeder extends Seeder
{
    public function run(): void
    {
        $this->rule('Base fare', PricingRuleType::BaseFare, ['amount_minor' => 1500]);

        // The first 1.5 km is inside the base fare; beyond that, 3 EGP per
        // started kilometre, which is how local couriers already quote.
        $this->rule('Distance', PricingRuleType::PerKilometre, [
            'rate_minor_per_km' => 300,
            'free_units' => 1500,
        ]);

        $this->rule('Minimum fare', PricingRuleType::MinimumFare, ['amount_minor' => 1500]);

        $this->rule('Express surcharge', PricingRuleType::PrioritySurcharge, [
            'priority' => DeliveryPriority::Express,
            'percentage_bps' => 3000,
        ]);

        $this->rule('Scheduled discount', PricingRuleType::PrioritySurcharge, [
            'priority' => DeliveryPriority::Scheduled,
            'percentage_bps' => -1000,
        ]);

        $this->rule('Large parcel', PricingRuleType::PackageSurcharge, [
            'package_size' => PackageSize::Large,
            'amount_minor' => 1000,
        ]);

        $this->rule('Bulky parcel', PricingRuleType::PackageSurcharge, [
            'package_size' => PackageSize::Bulky,
            'amount_minor' => 2500,
        ]);

        // Handling cash costs the network real time and real risk, so it is
        // priced rather than absorbed.
        $this->rule('Cash handling', PricingRuleType::CodHandling, [
            'percentage_bps' => 100,
        ]);

        // Late-night work is harder to staff; the surcharge is what makes a
        // rider willing to take it.
        $this->rule('Late night', PricingRuleType::TimeWindowSurcharge, [
            'percentage_bps' => 2000,
            'active_from' => '23:00',
            'active_until' => '05:00',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function rule(string $name, PricingRuleType $type, array $attributes): void
    {
        PricingRule::updateOrCreate(
            ['name' => $name, 'delivery_company_id' => null],
            array_merge([
                'type' => $type,
                'evaluation_order' => $type->evaluationOrder(),
                'is_active' => true,
                'amount_minor' => 0,
                'rate_minor_per_km' => 0,
                'percentage_bps' => 0,
                'free_units' => 0,
            ], $attributes),
        );
    }
}
