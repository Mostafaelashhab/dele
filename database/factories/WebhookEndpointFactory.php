<?php

namespace Database\Factories;

use App\Enums\WebhookEvent;
use App\Models\Business;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => (new Business)->getMorphClass(),
            'owner_id' => Business::factory(),
            'url' => 'https://example.test/webhooks/banha',
            'events' => WebhookEvent::values(),
            'is_active' => true,
        ];
    }
}
