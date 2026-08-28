<?php

namespace Database\Factories;

use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiClient>
 */
class ApiClientFactory extends Factory
{
    protected $model = ApiClient::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => (new Business)->getMorphClass(),
            'owner_id' => Business::factory(),
            'name' => fake()->company().' Integration',
            'status' => ApiClientStatus::Active,
            'environment' => 'test',
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => ApiClientStatus::Suspended]);
    }
}
