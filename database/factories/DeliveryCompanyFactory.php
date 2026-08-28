<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\DeliveryCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeliveryCompany>
 */
class DeliveryCompanyFactory extends Factory
{
    protected $model = DeliveryCompany::class;

    /**
     * Metrics start mid-range rather than at zero, so a factory-built company
     * behaves like an established one in matching tests unless a state says
     * otherwise.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Delivery';

        return [
            'name' => $name,
            'name_ar' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'contact_person' => fake()->name(),
            'phone' => '011'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'status' => AccountStatus::Active,
            'provider' => 'internal',
            'auto_accept_offers' => false,
            'max_concurrent_deliveries' => 50,
            'commission_bps' => 1200,
            'settlement_period' => 'weekly',
            'rating_bps' => fake()->numberBetween(3500, 4900),
            'acceptance_rate_bps' => fake()->numberBetween(6000, 9500),
            'completion_rate_bps' => fake()->numberBetween(8500, 9900),
            'average_pickup_minutes' => fake()->numberBetween(6, 18),
            'completed_deliveries_count' => fake()->numberBetween(20, 600),
            'metrics_updated_at' => now(),
            'onboarded_at' => now(),
            'latitude' => 30.4599 + fake()->randomFloat(4, -0.01, 0.01),
            'longitude' => 31.1837 + fake()->randomFloat(4, -0.01, 0.01),
        ];
    }

    /**
     * A company with no track record, to exercise the cold-start path in the
     * reliability and acceptance scorers.
     */
    public function newlyOnboarded(): static
    {
        return $this->state(fn () => [
            'rating_bps' => 0,
            'acceptance_rate_bps' => 0,
            'completion_rate_bps' => 0,
            'completed_deliveries_count' => 0,
            'metrics_updated_at' => null,
        ]);
    }

    public function autoAccepting(): static
    {
        return $this->state(fn () => ['auto_accept_offers' => true]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }
}
