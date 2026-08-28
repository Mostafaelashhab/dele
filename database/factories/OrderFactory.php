<?php

namespace Database\Factories;

use App\Domain\Orders\OrderNumberGenerator;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Enums\DeliveryPriority;
use App\Enums\OrderStatus;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Models\Business;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'number' => app(OrderNumberGenerator::class)->generate(),
            'status' => OrderStatus::Pending,
            'pickup' => $this->snapshot(30.4620, 31.1840),
            'dropoff' => $this->snapshot(30.4520, 31.1930),
            'priority' => DeliveryPriority::Standard,
            'package_size' => PackageSize::Small,
            'payment_type' => PaymentType::Prepaid,
            'cod_amount_minor' => 0,
            'declared_value_minor' => 0,
            'currency' => 'EGP',
            'placed_at' => now(),
        ];
    }

    public function cashOnDelivery(int $amountMinor = 15000): static
    {
        return $this->state(fn () => [
            'payment_type' => PaymentType::CashOnDelivery,
            'cod_amount_minor' => $amountMinor,
        ]);
    }

    public function priority(DeliveryPriority $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }

    public function size(PackageSize $size): static
    {
        return $this->state(fn () => ['package_size' => $size]);
    }

    public function between(Zone $pickup, Zone $dropoff): static
    {
        return $this->state(fn () => [
            'pickup_zone_id' => $pickup->id,
            'dropoff_zone_id' => $dropoff->id,
            'pickup' => $this->snapshot(
                $pickup->centroid_latitude,
                $pickup->centroid_longitude,
                $pickup->name,
                $pickup->id,
            ),
            'dropoff' => $this->snapshot(
                $dropoff->centroid_latitude,
                $dropoff->centroid_longitude,
                $dropoff->name,
                $dropoff->id,
            ),
        ]);
    }

    private function snapshot(
        float $latitude,
        float $longitude,
        ?string $area = null,
        ?string $zoneId = null,
    ): LocationSnapshot {
        return new LocationSnapshot(
            contactName: fake()->name(),
            contactPhone: '010'.fake()->numerify('########'),
            addressLine: fake()->streetAddress(),
            area: $area ?? fake()->streetName(),
            city: 'Banha',
            latitude: $latitude,
            longitude: $longitude,
            zoneId: $zoneId,
        );
    }
}
