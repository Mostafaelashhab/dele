<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'business_id' => fn (array $attributes) => Order::find($attributes['order_id'])?->business_id
                ?? Business::factory(),
            'status' => DeliveryStatus::Pending,
            'attempt' => 1,
            'provider' => 'internal',
            'distance_meters' => fake()->numberBetween(1200, 9000),
            'estimated_minutes' => fake()->numberBetween(15, 45),
            'currency' => 'EGP',
            'price_minor' => 2500,
            'platform_fee_minor' => 300,
            'company_payout_minor' => 2200,
            'rider_payout_minor' => 1540,
        ];
    }

    public function status(DeliveryStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    /**
     * A finished delivery with the milestone timestamps a metric would read,
     * so duration and rate calculations have something coherent to work on.
     */
    public function delivered(?int $minutesAgo = null): static
    {
        $deliveredAt = now()->subMinutes($minutesAgo ?? fake()->numberBetween(10, 600));

        return $this->state(fn () => [
            'status' => DeliveryStatus::Delivered,
            'created_at' => $deliveredAt->copy()->subMinutes(fake()->numberBetween(25, 55)),
            'accepted_at' => $deliveredAt->copy()->subMinutes(fake()->numberBetween(20, 45)),
            'assigned_at' => $deliveredAt->copy()->subMinutes(fake()->numberBetween(18, 40)),
            'picked_up_at' => $deliveredAt->copy()->subMinutes(fake()->numberBetween(10, 25)),
            'in_transit_at' => $deliveredAt->copy()->subMinutes(fake()->numberBetween(8, 20)),
            'delivered_at' => $deliveredAt,
        ]);
    }

    /**
     * A rider standing at the customer's door, which is the only state a
     * delivery may be closed from.
     */
    public function arrivedAtDestination(): static
    {
        return $this->state(function (): array {
            $company = DeliveryCompany::factory()->create();

            return [
                'delivery_company_id' => $company->id,
                'rider_id' => Rider::factory()->for($company)->create()->id,
                'status' => DeliveryStatus::ArrivedAtDestination,
                'accepted_at' => now()->subMinutes(30),
                'assigned_at' => now()->subMinutes(28),
                'picked_up_at' => now()->subMinutes(15),
                'in_transit_at' => now()->subMinutes(12),
                'arrived_at_destination_at' => now()->subMinute(),
            ];
        });
    }

    public function failed(string $reason = 'customer_unreachable'): static
    {
        return $this->state(fn () => [
            'status' => DeliveryStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }
}
