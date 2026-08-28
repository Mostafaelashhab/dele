<?php

namespace App\Domain\Providers;

use App\Models\Delivery;
use App\Models\Order;

/**
 * The provider-facing view of a delivery: enough to quote it, request it, or
 * look it up, and nothing about how the platform prices or ranks it.
 */
final readonly class DeliveryRequest
{
    public function __construct(
        public Order $order,
        public Delivery $delivery,
    ) {}

    public static function for(Delivery $delivery): self
    {
        return new self($delivery->order, $delivery);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'reference' => $this->order->number,
            'pickup' => $this->order->pickupSnapshot()->jsonSerialize(),
            'dropoff' => $this->order->dropoffSnapshot()->jsonSerialize(),
            'priority' => $this->order->priority->value,
            'package_size' => $this->order->package_size->value,
            'payment_type' => $this->order->payment_type->value,
            'cod_amount_minor' => $this->order->cod_amount_minor?->minor ?? 0,
            'distance_meters' => $this->delivery->distance_meters,
            'notes' => $this->order->notes,
        ];
    }
}
