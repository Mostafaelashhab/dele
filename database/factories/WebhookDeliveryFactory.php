<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEvent;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event' => WebhookEvent::OrderCreated,
            'event_id' => 'evt_'.Str::lower((string) Str::ulid()),
            'payload' => ['id' => 'evt_test', 'type' => 'order.created', 'data' => []],
            'status' => WebhookDeliveryStatus::Pending,
        ];
    }
}
