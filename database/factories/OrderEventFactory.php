<?php

namespace Database\Factories;

use App\Enums\OrderEventType;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderEvent>
 */
class OrderEventFactory extends Factory
{
    protected $model = OrderEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'type' => OrderEventType::OrderCreated,
            'actor_type' => 'system',
            'is_customer_visible' => true,
            'occurred_at' => now(),
        ];
    }
}
