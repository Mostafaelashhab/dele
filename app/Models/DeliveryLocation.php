<?php

namespace App\Models;

use App\Domain\Shared\ValueObjects\GeoPoint;
use Database\Factories\DeliveryLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single GPS breadcrumb. High write volume, so timestamps are reduced to a
 * single recorded_at column and the model is deliberately thin.
 */
#[Fillable([
    'rider_id', 'delivery_id', 'latitude', 'longitude', 'accuracy_meters',
    'heading_degrees', 'speed_kmh', 'battery_percent', 'status', 'recorded_at',
])]
class DeliveryLocation extends Model
{
    /** @use HasFactory<DeliveryLocationFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'integer',
            'heading_degrees' => 'integer',
            'speed_kmh' => 'integer',
            'battery_percent' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Rider, $this>
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    #[Scope]
    protected function recent(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    public function point(): GeoPoint
    {
        return new GeoPoint($this->latitude, $this->longitude);
    }
}
