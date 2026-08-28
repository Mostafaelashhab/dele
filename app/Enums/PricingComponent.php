<?php

namespace App\Enums;

/**
 * Every line the pricing engine can emit. A quote is the ordered sum of
 * these components, which is what makes a price explainable after the fact.
 */
enum PricingComponent: string
{
    case Base = 'base';
    case Distance = 'distance';
    case ZoneSurcharge = 'zone_surcharge';
    case PrioritySurcharge = 'priority_surcharge';
    case PackageSurcharge = 'package_surcharge';
    case TimeSurcharge = 'time_surcharge';
    case DemandSurcharge = 'demand_surcharge';
    case CodHandling = 'cod_handling';
    case MinimumFareAdjustment = 'minimum_fare_adjustment';
    case Rounding = 'rounding';
    case Discount = 'discount';

    public function label(): string
    {
        return __('pricing.component.'.$this->value);
    }
}
