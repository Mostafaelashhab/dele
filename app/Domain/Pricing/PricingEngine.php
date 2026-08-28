<?php

namespace App\Domain\Pricing;

use App\Domain\Shared\ValueObjects\Money;
use App\Enums\PricingComponent;
use App\Enums\PricingRuleType;
use App\Models\PricingRule;
use Illuminate\Support\Collection;

/**
 * Turns a delivery request into an itemised price.
 *
 * Every amount comes from a pricing_rules row or from config — nothing is
 * hard-coded here — and every amount produces a labelled line, so the total
 * can always be explained back to whoever paid it.
 */
class PricingEngine
{
    public function __construct(
        private readonly PricingRuleResolver $resolver,
    ) {}

    public function quote(PricingContext $context): PriceQuote
    {
        $rules = $this->resolver->resolve($context);
        $lines = new Collection;

        // A zone-pair flat rate is a complete price for the leg, so it
        // replaces the base fare and distance charge rather than stacking.
        $zonePairRule = $rules->firstWhere('type', PricingRuleType::ZonePairFlat);

        if ($zonePairRule !== null) {
            $lines->push($this->zonePairLine($zonePairRule, $context));
        } else {
            $lines->push($this->baseLine($rules->firstWhere('type', PricingRuleType::BaseFare)));
            $lines->push($this->distanceLine($rules->firstWhere('type', PricingRuleType::PerKilometre), $context));
        }

        $lines->push($this->zoneSurchargeLine($rules->firstWhere('type', PricingRuleType::ZoneFlat), $context));
        $lines->push($this->packageLine($rules->firstWhere('type', PricingRuleType::PackageSurcharge), $context));

        $subtotal = $this->sum($lines);

        $lines->push($this->timeWindowLine($rules->firstWhere('type', PricingRuleType::TimeWindowSurcharge), $subtotal));

        $subtotal = $this->sum($lines);

        $lines->push($this->priorityLine($rules->firstWhere('type', PricingRuleType::PrioritySurcharge), $context, $subtotal));
        $lines->push($this->codLine($rules->firstWhere('type', PricingRuleType::CodHandling), $context));
        $lines->push($this->serviceAreaSurchargeLine($context));

        $subtotal = $this->sum($lines);

        $lines->push($this->minimumFareLine($rules->firstWhere('type', PricingRuleType::MinimumFare), $subtotal));

        $subtotal = $this->sum($lines);

        $lines->push($this->roundingLine($subtotal));

        $total = $this->sum($lines);

        $platformFee = $this->platformFee($total, $context);
        $companyPayout = $total->minus($platformFee);
        $riderPayout = $companyPayout->percentage(
            (int) config('platform.settlements.rider_share_bps', 7000)
        );

        return new PriceQuote(
            lines: $lines->filter()->values(),
            total: $total,
            platformFee: $platformFee,
            companyPayout: $companyPayout,
            riderPayout: $riderPayout,
            distanceMeters: $context->distanceMeters,
            estimatedMinutes: $context->estimatedMinutes,
            deliveryCompanyId: $context->deliveryCompany?->id,
            context: $context->toArray(),
        );
    }

    /**
     * The platform's cut. Taken from the business's negotiated rate when it
     * has one, otherwise the platform default, and never below the floor.
     */
    public function platformFee(Money $total, PricingContext $context): Money
    {
        $basisPoints = $context->business?->platformFeeBasisPoints()
            ?? (int) config('platform.pricing.platform_fee.percentage_bps');

        $fee = $total->percentage($basisPoints)
            ->plus(Money::ofMinor((int) config('platform.pricing.platform_fee.fixed_minor', 0)));

        $minimum = Money::ofMinor((int) config('platform.pricing.platform_fee.minimum_minor', 0));

        return $total->isZero() ? Money::zero() : $fee->max($minimum)->min($total);
    }

    private function baseLine(?PricingRule $rule): PriceLine
    {
        $amount = $rule?->amount_minor
            ?? Money::ofMinor((int) config('platform.pricing.default_base_minor'));

        return new PriceLine(
            component: PricingComponent::Base,
            label: __('pricing.component.base'),
            amount: $amount,
            ruleId: $rule?->id,
        );
    }

    private function distanceLine(?PricingRule $rule, PricingContext $context): PriceLine
    {
        $ratePerKm = $rule?->rate_minor_per_km
            ?? (int) config('platform.pricing.default_per_km_minor');

        $freeMeters = $rule?->free_units
            ?? (int) config('platform.pricing.free_distance_meters');

        $chargeableMeters = max(0, $context->distanceMeters - $freeMeters);

        // Charge per whole kilometre started, which is how local couriers
        // already quote and what businesses expect to see.
        $chargeableKm = (int) ceil($chargeableMeters / 1000);

        return new PriceLine(
            component: PricingComponent::Distance,
            label: __('pricing.component.distance'),
            amount: Money::ofMinor($ratePerKm * $chargeableKm),
            ruleId: $rule?->id,
            detail: [
                'distance_meters' => $context->distanceMeters,
                'free_meters' => $freeMeters,
                'chargeable_km' => $chargeableKm,
                'rate_minor_per_km' => $ratePerKm,
            ],
        );
    }

    private function zonePairLine(PricingRule $rule, PricingContext $context): PriceLine
    {
        return new PriceLine(
            component: PricingComponent::ZoneSurcharge,
            label: __('pricing.component.zone_pair', [
                'from' => $context->pickupZone?->displayName() ?? '—',
                'to' => $context->dropoffZone?->displayName() ?? '—',
            ]),
            amount: $rule->amount_minor ?? Money::zero(),
            ruleId: $rule->id,
            detail: [
                'pickup_zone_id' => $context->pickupZone?->id,
                'dropoff_zone_id' => $context->dropoffZone?->id,
            ],
        );
    }

    private function zoneSurchargeLine(?PricingRule $rule, PricingContext $context): ?PriceLine
    {
        if ($rule === null) {
            return null;
        }

        return new PriceLine(
            component: PricingComponent::ZoneSurcharge,
            label: __('pricing.component.zone_surcharge', [
                'zone' => $context->dropoffZone?->displayName() ?? '—',
            ]),
            amount: $rule->amount_minor ?? Money::zero(),
            ruleId: $rule->id,
        );
    }

    private function packageLine(?PricingRule $rule, PricingContext $context): ?PriceLine
    {
        if ($rule === null) {
            return null;
        }

        return new PriceLine(
            component: PricingComponent::PackageSurcharge,
            label: __('pricing.component.package_surcharge', [
                'size' => $context->packageSize->label(),
            ]),
            amount: $rule->amount_minor ?? Money::zero(),
            ruleId: $rule->id,
        );
    }

    private function timeWindowLine(?PricingRule $rule, Money $subtotal): ?PriceLine
    {
        if ($rule === null) {
            return null;
        }

        $amount = $rule->percentage_bps > 0
            ? $subtotal->percentage($rule->percentage_bps)
            : ($rule->amount_minor ?? Money::zero());

        return new PriceLine(
            component: PricingComponent::TimeSurcharge,
            label: $rule->name,
            amount: $amount,
            ruleId: $rule->id,
            detail: ['percentage_bps' => $rule->percentage_bps],
        );
    }

    /**
     * Priority pricing comes from a rule when the operator has defined one,
     * otherwise from the multiplier attached to the priority itself.
     */
    private function priorityLine(?PricingRule $rule, PricingContext $context, Money $subtotal): ?PriceLine
    {
        $basisPoints = $rule?->percentage_bps > 0
            ? $rule->percentage_bps
            : $context->priority->multiplierBasisPoints() - 10000;

        if ($basisPoints === 0 && $rule?->amount_minor === null) {
            return null;
        }

        $amount = $basisPoints !== 0
            ? $subtotal->percentage($basisPoints)
            : ($rule?->amount_minor ?? Money::zero());

        return new PriceLine(
            component: PricingComponent::PrioritySurcharge,
            label: __('pricing.component.priority_surcharge', [
                'priority' => $context->priority->label(),
            ]),
            amount: $amount,
            ruleId: $rule?->id,
            detail: ['percentage_bps' => $basisPoints],
        );
    }

    private function codLine(?PricingRule $rule, PricingContext $context): ?PriceLine
    {
        if (! $context->paymentType->requiresCollection() || $context->codAmount->isZero()) {
            return null;
        }

        $amount = $rule?->percentage_bps > 0
            ? $context->codAmount->percentage($rule->percentage_bps)
            : ($rule?->amount_minor ?? Money::zero());

        if ($amount->isZero()) {
            return null;
        }

        return new PriceLine(
            component: PricingComponent::CodHandling,
            label: __('pricing.component.cod_handling'),
            amount: $amount,
            ruleId: $rule?->id,
            detail: ['cod_amount_minor' => $context->codAmount->minor],
        );
    }

    /**
     * A company may charge extra for a zone it serves reluctantly. The
     * surcharge lives on the service-area pivot, not in a pricing rule.
     */
    private function serviceAreaSurchargeLine(PricingContext $context): ?PriceLine
    {
        $company = $context->deliveryCompany;
        $zone = $context->dropoffZone;

        if ($company === null || $zone === null) {
            return null;
        }

        $area = $company->serviceAreas->firstWhere('id', $zone->id);
        $surcharge = $area?->pivot?->surcharge_minor ?? 0;

        if ($surcharge <= 0) {
            return null;
        }

        return new PriceLine(
            component: PricingComponent::ZoneSurcharge,
            label: __('pricing.component.company_zone_surcharge', ['zone' => $zone->displayName()]),
            amount: Money::ofMinor((int) $surcharge),
            detail: ['delivery_company_id' => $company->id, 'zone_id' => $zone->id],
        );
    }

    private function minimumFareLine(?PricingRule $rule, Money $subtotal): ?PriceLine
    {
        $minimum = $rule?->amount_minor
            ?? Money::ofMinor((int) config('platform.pricing.minimum_fare_minor'));

        if (! $subtotal->lessThan($minimum)) {
            return null;
        }

        return new PriceLine(
            component: PricingComponent::MinimumFareAdjustment,
            label: __('pricing.component.minimum_fare_adjustment'),
            amount: $minimum->minus($subtotal),
            ruleId: $rule?->id,
            detail: ['minimum_minor' => $minimum->minor],
        );
    }

    /**
     * Round the total up to a clean increment. Riders handle cash, so a price
     * that needs 30 piastres of change is an operational problem.
     */
    private function roundingLine(Money $subtotal): ?PriceLine
    {
        $increment = (int) config('platform.pricing.rounding_increment_minor', 0);

        if ($increment <= 1) {
            return null;
        }

        $rounded = $subtotal->roundUpTo($increment);
        $difference = $rounded->minus($subtotal);

        if ($difference->isZero()) {
            return null;
        }

        return new PriceLine(
            component: PricingComponent::Rounding,
            label: __('pricing.component.rounding'),
            amount: $difference,
            detail: ['increment_minor' => $increment],
        );
    }

    /**
     * @param  Collection<int, PriceLine|null>  $lines
     */
    private function sum(Collection $lines): Money
    {
        return $lines->filter()->reduce(
            fn (Money $carry, PriceLine $line) => $carry->plus($line->amount),
            Money::zero(),
        );
    }
}
