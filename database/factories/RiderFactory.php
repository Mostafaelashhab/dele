<?php

namespace Database\Factories;

use App\Enums\RiderStatus;
use App\Enums\VehicleType;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rider>
 */
class RiderFactory extends Factory
{
    protected $model = Rider::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_company_id' => DeliveryCompany::factory(),
            'name' => fake()->name('male'),
            'phone' => '012'.fake()->numerify('########'),
            'status' => RiderStatus::Offline,
            'vehicle_type' => VehicleType::Motorcycle,
            'vehicle_identifier' => fake()->bothify('?? ### ??'),
            'max_concurrent_deliveries' => 2,
            'active_deliveries_count' => 0,
            'rating_bps' => fake()->numberBetween(3800, 5000),
            'acceptance_rate_bps' => fake()->numberBetween(7000, 9800),
            'completion_rate_bps' => fake()->numberBetween(9000, 9900),
            'completed_deliveries_count' => fake()->numberBetween(0, 300),
        ];
    }

    /**
     * Online, located, and available — the state dispatch actually looks for.
     */
    public function online(?float $latitude = null, ?float $longitude = null): static
    {
        return $this->state(fn () => [
            'status' => RiderStatus::Online,
            'current_latitude' => $latitude ?? 30.4599 + fake()->randomFloat(4, -0.012, 0.012),
            'current_longitude' => $longitude ?? 31.1837 + fake()->randomFloat(4, -0.012, 0.012),
            'location_updated_at' => now(),
            'last_seen_at' => now(),
            'went_online_at' => now()->subMinutes(fake()->numberBetween(5, 240)),
        ]);
    }

    public function atCapacity(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_deliveries_count' => $attributes['max_concurrent_deliveries'] ?? 2,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => RiderStatus::Suspended]);
    }

    public function vehicle(VehicleType $type): static
    {
        return $this->state(fn () => ['vehicle_type' => $type]);
    }
}
