<?php

namespace Database\Factories;

use App\Models\DeliveryLocation;
use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryLocation>
 */
class DeliveryLocationFactory extends Factory
{
    protected $model = DeliveryLocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rider_id' => Rider::factory(),
            'latitude' => 30.4599 + fake()->randomFloat(4, -0.02, 0.02),
            'longitude' => 31.1837 + fake()->randomFloat(4, -0.02, 0.02),
            'accuracy_meters' => fake()->numberBetween(5, 40),
            'recorded_at' => now(),
        ];
    }
}
