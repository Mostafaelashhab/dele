<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderEvent
 */
class OrderEventResource extends JsonResource
{
    /**
     * The actor is exposed as a type only. Which named dispatcher clicked
     * accept is an internal matter of the delivery company, not something an
     * integrator's log should record.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'object' => 'event',
            'type' => $this->type->value,
            'label' => $this->type->label(),
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status?->value,
            'actor_type' => $this->actor_type,
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
