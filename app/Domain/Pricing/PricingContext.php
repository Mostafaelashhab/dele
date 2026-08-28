<?php

namespace App\Domain\Pricing;

use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Support\Carbon;

/**
 * Everything the pricing engine is allowed to look at. Passing a context
 * object rather than a model keeps the engine testable without touching the
 * database, and makes it obvious that nothing else influences a price.
 */
final readonly class PricingContext
{
    public function __construct(
        public int $distanceMeters,
        public int $estimatedMinutes,
        public DeliveryPriority $priority,
        public PackageSize $packageSize,
        public PaymentType $paymentType,
        public Money $codAmount,
        public ?Zone $pickupZone = null,
        public ?Zone $dropoffZone = null,
        public ?Business $business = null,
        public ?DeliveryCompany $deliveryCompany = null,
        public ?Carbon $moment = null,
    ) {}

    public static function fromOrder(
        Order $order,
        int $distanceMeters,
        int $estimatedMinutes,
        ?DeliveryCompany $company = null,
    ): self {
        return new self(
            distanceMeters: $distanceMeters,
            estimatedMinutes: $estimatedMinutes,
            priority: $order->priority,
            packageSize: $order->package_size,
            paymentType: $order->payment_type,
            codAmount: $order->cod_amount_minor ?? Money::zero(),
            pickupZone: $order->pickupZone,
            dropoffZone: $order->dropoffZone,
            business: $order->business,
            deliveryCompany: $company,
            moment: now(),
        );
    }

    public function forCompany(?DeliveryCompany $company): self
    {
        return new self(
            distanceMeters: $this->distanceMeters,
            estimatedMinutes: $this->estimatedMinutes,
            priority: $this->priority,
            packageSize: $this->packageSize,
            paymentType: $this->paymentType,
            codAmount: $this->codAmount,
            pickupZone: $this->pickupZone,
            dropoffZone: $this->dropoffZone,
            business: $this->business,
            deliveryCompany: $company,
            moment: $this->moment,
        );
    }

    public function at(): Carbon
    {
        return $this->moment ?? now();
    }

    public function distanceKilometres(): float
    {
        return $this->distanceMeters / 1000;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'distance_meters' => $this->distanceMeters,
            'estimated_minutes' => $this->estimatedMinutes,
            'priority' => $this->priority->value,
            'package_size' => $this->packageSize->value,
            'payment_type' => $this->paymentType->value,
            'cod_amount_minor' => $this->codAmount->minor,
            'pickup_zone_id' => $this->pickupZone?->id,
            'dropoff_zone_id' => $this->dropoffZone?->id,
            'business_id' => $this->business?->id,
            'delivery_company_id' => $this->deliveryCompany?->id,
        ];
    }
}
