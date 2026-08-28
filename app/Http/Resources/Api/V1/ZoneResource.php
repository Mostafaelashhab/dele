<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Zone
 */
class ZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->code,
            'object' => 'zone',
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'city' => $this->city,
            'centre' => ['lat' => $this->centroid_latitude, 'lng' => $this->centroid_longitude],
            'radius_meters' => $this->radius_meters,
            'base_price' => $this->basePrice()->minor,
            'estimated_minutes' => $this->estimated_minutes,
            'currency' => config('platform.currency.code'),
        ];
    }
}
