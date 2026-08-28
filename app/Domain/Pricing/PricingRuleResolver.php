<?php

namespace App\Domain\Pricing;

use App\Models\PricingRule;
use Illuminate\Support\Collection;

/**
 * Selects which pricing rules apply to a given context.
 *
 * A company's own rules and the platform defaults are loaded together, then
 * narrowed: for each rule type the most specifically scoped matching rule
 * wins, so a company override silently supersedes the platform default
 * without either being special-cased in code.
 */
class PricingRuleResolver
{
    /**
     * @return Collection<int, PricingRule>
     */
    public function resolve(PricingContext $context): Collection
    {
        return $this->candidates($context)
            ->filter(fn (PricingRule $rule) => $this->matches($rule, $context))
            ->groupBy(fn (PricingRule $rule) => $rule->type->value)
            ->map(fn (Collection $group) => $this->mostSpecific($group))
            ->values()
            ->sortBy([
                fn (PricingRule $rule) => $rule->type->evaluationOrder(),
                fn (PricingRule $rule) => $rule->evaluation_order,
            ])
            ->values();
    }

    /**
     * @return Collection<int, PricingRule>
     */
    protected function candidates(PricingContext $context): Collection
    {
        return PricingRule::query()
            ->active()
            ->forCompany($context->deliveryCompany?->id)
            ->where(function ($query) use ($context): void {
                $query->whereNull('business_id');

                if ($context->business !== null) {
                    $query->orWhere('business_id', $context->business->id);
                }
            })
            ->get();
    }

    protected function matches(PricingRule $rule, PricingContext $context): bool
    {
        if ($rule->pickup_zone_id !== null && $rule->pickup_zone_id !== $context->pickupZone?->id) {
            return false;
        }

        if ($rule->dropoff_zone_id !== null && $rule->dropoff_zone_id !== $context->dropoffZone?->id) {
            return false;
        }

        if ($rule->priority !== null && $rule->priority !== $context->priority) {
            return false;
        }

        if ($rule->package_size !== null && $rule->package_size !== $context->packageSize) {
            return false;
        }

        if (! $rule->matchesDistance($context->distanceMeters)) {
            return false;
        }

        return $rule->isActiveAt($context->at());
    }

    /**
     * Break ties deterministically: tightest scope first, then the operator's
     * explicit evaluation_order, then the newest rule.
     *
     * @param  Collection<int, PricingRule>  $rules
     */
    protected function mostSpecific(Collection $rules): PricingRule
    {
        return $rules->sortByDesc(fn (PricingRule $rule) => sprintf(
            '%03d-%03d-%s',
            $rule->specificity(),
            $rule->evaluation_order,
            $rule->created_at?->timestamp ?? 0,
        ))->first();
    }
}
