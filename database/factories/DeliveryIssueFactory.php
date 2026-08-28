<?php

namespace Database\Factories;

use App\Enums\DeliveryIssueCategory;
use App\Enums\DeliveryIssueStatus;
use App\Models\Delivery;
use App\Models\DeliveryIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryIssue>
 */
class DeliveryIssueFactory extends Factory
{
    protected $model = DeliveryIssue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $delivery = Delivery::factory();

        return [
            'delivery_id' => $delivery,
            'order_id' => fn (array $attributes) => Delivery::findOrFail($attributes['delivery_id'])->order_id,
            'delivery_status' => fn (array $attributes) => Delivery::findOrFail($attributes['delivery_id'])->status,
            'category' => fake()->randomElement(DeliveryIssueCategory::cases()),
            'status' => DeliveryIssueStatus::Open,
            'note' => fake()->boolean(60) ? fake()->sentence() : null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => DeliveryIssueStatus::Resolved,
            'resolved_at' => now(),
            'resolution_note' => fake()->sentence(),
        ]);
    }
}
