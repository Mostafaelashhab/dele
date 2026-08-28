<?php

namespace Database\Factories;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Zone>
 */
class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    /**
     * Coordinates are jittered around central Banha so generated zones sit in
     * a plausible geography and distance maths produces realistic numbers.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->streetName();

        return [
            'code' => Str::upper(Str::random(6)),
            'name' => $name,
            'name_ar' => $name,
            'city' => 'Banha',
            'governorate' => 'Qalyubia',
            'centroid_latitude' => 30.4599 + fake()->randomFloat(4, -0.02, 0.02),
            'centroid_longitude' => 31.1837 + fake()->randomFloat(4, -0.02, 0.02),
            'radius_meters' => fake()->numberBetween(800, 2500),
            'base_price_minor' => fake()->numberBetween(1500, 3000),
            'estimated_minutes' => fake()->numberBetween(15, 40),
            'sort_order' => fake()->numberBetween(0, 50),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function at(float $latitude, float $longitude, int $radius = 1500): static
    {
        return $this->state(fn () => [
            'centroid_latitude' => $latitude,
            'centroid_longitude' => $longitude,
            'radius_meters' => $radius,
        ]);
    }
}
