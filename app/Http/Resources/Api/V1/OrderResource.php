<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $delivery = $this->whenLoaded('currentDelivery');

        return [
            'id' => $this->number,
            'object' => 'order',
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),

            'pickup' => $this->publicLocation($this->pickupSnapshot()),
            'dropoff' => $this->publicLocation($this->dropoffSnapshot()),

            'priority' => $this->priority->value,
            'package_size' => $this->package_size->value,
            'payment_type' => $this->payment_type->value,
            'cod_amount' => $this->cod_amount_minor?->minor ?? 0,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            'delivery' => $delivery instanceof Delivery
                ? new DeliveryResource($delivery)
                : null,

            // Convenience mirrors of the current delivery, so a simple
            // integration never has to reach into a nested object.
            'price' => $delivery instanceof Delivery ? $delivery->price()->minor : null,
            'tracking_url' => $delivery instanceof Delivery ? $delivery->trackingUrl() : null,

            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publicLocation(LocationSnapshot $snapshot): array
    {
        return [
            'name' => $snapshot->contactName,
            'phone' => $snapshot->contactPhone,
            'address' => $snapshot->addressLine,
            'area' => $snapshot->area,
            'city' => $snapshot->city,
            'landmark' => $snapshot->landmark,
            'lat' => $snapshot->latitude,
            'lng' => $snapshot->longitude,
        ];
    }
}
