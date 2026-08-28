<?php

namespace App\Enums;

enum PricingRuleType: string
{
    case BaseFare = 'base_fare';
    case PerKilometre = 'per_kilometre';
    case ZoneFlat = 'zone_flat';
    case ZonePairFlat = 'zone_pair_flat';
    case PrioritySurcharge = 'priority_surcharge';
    case PackageSurcharge = 'package_surcharge';
    case TimeWindowSurcharge = 'time_window_surcharge';
    case CodHandling = 'cod_handling';
    case MinimumFare = 'minimum_fare';

    public function component(): PricingComponent
    {
        return match ($this) {
            self::BaseFare => PricingComponent::Base,
            self::PerKilometre => PricingComponent::Distance,
            self::ZoneFlat, self::ZonePairFlat => PricingComponent::ZoneSurcharge,
            self::PrioritySurcharge => PricingComponent::PrioritySurcharge,
            self::PackageSurcharge => PricingComponent::PackageSurcharge,
            self::TimeWindowSurcharge => PricingComponent::TimeSurcharge,
            self::CodHandling => PricingComponent::CodHandling,
            self::MinimumFare => PricingComponent::MinimumFareAdjustment,
        };
    }

    /**
     * Rules are applied in this order so that percentage based surcharges
     * always see a fully formed subtotal.
     */
    public function evaluationOrder(): int
    {
        return match ($this) {
            self::BaseFare => 10,
            self::PerKilometre => 20,
            self::ZoneFlat, self::ZonePairFlat => 30,
            self::PackageSurcharge => 40,
            self::TimeWindowSurcharge => 50,
            self::PrioritySurcharge => 60,
            self::CodHandling => 70,
            self::MinimumFare => 90,
        };
    }

    public function label(): string
    {
        return __('pricing.rule.'.$this->value);
    }
}
