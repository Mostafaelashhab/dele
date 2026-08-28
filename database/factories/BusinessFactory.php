<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'name_ar' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'category' => fake()->randomElement([
                'restaurant', 'pharmacy', 'grocery', 'clothing', 'electronics', 'online',
            ]),
            'contact_person' => fake()->name(),
            'phone' => '010'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'status' => AccountStatus::Active,
            'address_line' => fake()->streetAddress(),
            'latitude' => 30.4599 + fake()->randomFloat(4, -0.015, 0.015),
            'longitude' => 31.1837 + fake()->randomFloat(4, -0.015, 0.015),
            'onboarded_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }
}
