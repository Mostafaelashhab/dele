<?php

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAssignment>
 */
class DeliveryAssignmentFactory extends Factory
{
    protected $model = DeliveryAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'rider_id' => Rider::factory(),
            'delivery_company_id' => DeliveryCompany::factory(),
            'status' => AssignmentStatus::Offered,
            'payout_minor' => 1540,
            'currency' => 'EGP',
            'offered_at' => now(),
            'expires_at' => now()->addSeconds(60),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => AssignmentStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'offered_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinutes(4),
        ]);
    }
}
