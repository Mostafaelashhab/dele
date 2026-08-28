<?php

namespace Database\Factories;

use App\Enums\PricingRuleType;
use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingRule>
 */
class PricingRuleFactory extends Factory
{
    protected $model = PricingRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => PricingRuleType::BaseFare,
            'amount_minor' => 1500,
            'rate_minor_per_km' => 0,
            'percentage_bps' => 0,
            'free_units' => 0,
            'evaluation_order' => 10,
            'is_active' => true,
        ];
    }

    public function ofType(PricingRuleType $type): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'evaluation_order' => $type->evaluationOrder(),
        ]);
    }

    public function perKilometre(int $rateMinor, int $freeMeters = 0): static
    {
        return $this->state(fn () => [
            'type' => PricingRuleType::PerKilometre,
            'evaluation_order' => PricingRuleType::PerKilometre->evaluationOrder(),
            'rate_minor_per_km' => $rateMinor,
            'free_units' => $freeMeters,
            'amount_minor' => 0,
        ]);
    }

    public function forCompany(string $companyId): static
    {
        return $this->state(fn () => ['delivery_company_id' => $companyId]);
    }
}
