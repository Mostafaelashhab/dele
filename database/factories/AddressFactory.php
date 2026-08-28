<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => (new Business)->getMorphClass(),
            'owner_id' => Business::factory(),
            'label' => fake()->word(),
            'contact_name' => fake()->name(),
            'contact_phone' => '010'.fake()->numerify('########'),
            'address_line' => fake()->streetAddress(),
            'city' => 'Banha',
            'latitude' => 30.4599 + fake()->randomFloat(4, -0.015, 0.015),
            'longitude' => 31.1837 + fake()->randomFloat(4, -0.015, 0.015),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
