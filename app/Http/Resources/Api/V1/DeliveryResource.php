<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Delivery
 */
class DeliveryResource extends JsonResource
{
    /**
     * The public shape of a delivery.
     *
     * Internal ULIDs never appear — callers see the prefixed public_id — and
     * the rider is reduced to a first name and a vehicle, because an
     * integrator has no need for a courier's identity.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'object' => 'delivery',
            'order_id' => $this->whenLoaded('order', fn () => $this->order->number),
            'reference' => $this->whenLoaded('order', fn () => $this->order->reference),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'attempt' => $this->attempt,

            'price' => $this->price()->minor,
            'currency' => $this->currency,
            'price_breakdown' => $this->when(
                $request->boolean('include_breakdown'),
                fn () => $this->price_breakdown['lines'] ?? [],
            ),

            'distance_meters' => $this->distance_meters,
            'estimated_minutes' => $this->estimated_minutes,
            'estimated_delivery_at' => $this->estimated_delivery_at?->toIso8601String(),

            'delivery_company' => $this->whenLoaded('deliveryCompany', fn () => $this->deliveryCompany === null ? null : [
                'name' => $this->deliveryCompany->name,
                'phone' => $this->deliveryCompany->phone,
            ]),

            'rider' => $this->whenLoaded('rider', fn () => $this->rider === null ? null : [
                'first_name' => explode(' ', trim($this->rider->name))[0] ?? '',
                'vehicle_type' => $this->rider->vehicle_type->value,
            ]),

            /*
             * What the handover can be shown to have been.
             *
             * `verified` is the field an integrator should branch on: it is
             * true when either mechanism recorded the hand-off. The code
             * itself is never returned here — this resource is read by the
             * shop's systems, and a code readable before delivery would
             * defeat the point of it.
             */
            'proof_of_delivery' => $this->when($this->delivered_at !== null, fn () => [
                'received_by' => $this->received_by,
                'verified' => $this->hasProofOfDelivery(),
                'has_photo' => filled($this->proof_photo_path),
                'confirmed_by_code' => $this->confirmation_code_verified_at !== null,
                'confirmed_at' => $this->confirmation_code_verified_at?->toIso8601String(),
                'cod_collected' => $this->cod_collected_minor?->minor ?? 0,
            ]),

            'timestamps' => array_filter([
                'created_at' => $this->created_at?->toIso8601String(),
                'accepted_at' => $this->accepted_at?->toIso8601String(),
                'assigned_at' => $this->assigned_at?->toIso8601String(),
                'picked_up_at' => $this->picked_up_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'failed_at' => $this->failed_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            ]),

            'failure_reason' => $this->failure_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'tracking_url' => $this->trackingUrl(),
        ];
    }
}
