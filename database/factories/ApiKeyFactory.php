<?php

namespace Database\Factories;

use App\Models\ApiClient;
use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = 'bdn_'.Str::lower(Str::random(12));
        $secret = Str::random(40);

        return [
            'api_client_id' => ApiClient::factory(),
            'name' => 'Test key',
            'prefix' => $prefix,
            'key_hash' => hash('sha256', $prefix.'.'.$secret),
            'last_four' => Str::substr($secret, -4),
        ];
    }
}
