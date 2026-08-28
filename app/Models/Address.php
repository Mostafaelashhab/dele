<?php

namespace App\Models;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'owner_type', 'owner_id', 'zone_id', 'label', 'contact_name', 'contact_phone',
    'address_line', 'building', 'floor', 'apartment', 'landmark', 'area', 'city',
    'latitude', 'longitude', 'notes', 'is_default',
])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function point(): ?GeoPoint
    {
        return GeoPoint::tryMake($this->latitude, $this->longitude);
    }

    /**
     * Freeze this address onto an order so later edits never rewrite history.
     */
    public function toSnapshot(?string $contactName = null, ?string $contactPhone = null): LocationSnapshot
    {
        return new LocationSnapshot(
            contactName: $contactName ?? $this->contact_name ?? '',
            contactPhone: $contactPhone ?? $this->contact_phone ?? '',
            addressLine: $this->composedLine(),
            area: $this->area,
            city: $this->city,
            landmark: $this->landmark,
            notes: $this->notes,
            latitude: $this->latitude,
            longitude: $this->longitude,
            zoneId: $this->zone_id,
        );
    }

    public function composedLine(): string
    {
        return collect([
            $this->address_line,
            $this->building ? __('address.building').' '.$this->building : null,
            $this->floor ? __('address.floor').' '.$this->floor : null,
            $this->apartment ? __('address.apartment').' '.$this->apartment : null,
        ])->filter()->implode('، ');
    }
}
