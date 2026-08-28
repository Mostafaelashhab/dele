<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\Money;
use Database\Factories\ZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'parent_id', 'code', 'name', 'name_ar', 'city', 'governorate',
    'centroid_latitude', 'centroid_longitude', 'radius_meters', 'polygon',
    'base_price_minor', 'estimated_minutes', 'sort_order', 'is_active',
])]
class Zone extends Model
{
    /** @use HasFactory<ZoneFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'centroid_latitude' => 'float',
            'centroid_longitude' => 'float',
            'radius_meters' => 'integer',
            'polygon' => 'array',
            'base_price_minor' => MoneyCast::class,
            'estimated_minutes' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<DeliveryCompany, $this, Pivot>
     */
    public function deliveryCompanies(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryCompany::class, 'company_service_areas')
            ->withPivot(['accepts_pickup', 'accepts_dropoff', 'surcharge_minor'])
            ->withTimestamps();
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function centroid(): GeoPoint
    {
        return new GeoPoint($this->centroid_latitude, $this->centroid_longitude);
    }

    public function basePrice(): Money
    {
        return $this->base_price_minor ?? Money::zero();
    }

    /**
     * Whether a point falls inside this zone.
     *
     * Prefers the polygon when one is defined; otherwise falls back to the
     * centroid radius, which is what every seeded Banha zone uses today.
     */
    public function contains(GeoPoint $point): bool
    {
        if (filled($this->polygon)) {
            return $this->polygonContains($point);
        }

        return $this->centroid()->haversineMetresTo($point) <= $this->radius_meters;
    }

    public function distanceTo(GeoPoint $point): int
    {
        return $this->centroid()->haversineMetresTo($point);
    }

    public function displayName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name;
    }

    /**
     * Ray casting against the stored ring of [lng, lat] pairs.
     */
    private function polygonContains(GeoPoint $point): bool
    {
        $ring = $this->polygon['coordinates'][0] ?? $this->polygon;

        if (! is_array($ring) || count($ring) < 3) {
            return false;
        }

        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $ring[$i][0];
            $yi = (float) $ring[$i][1];
            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];

            $intersects = (($yi > $point->latitude) !== ($yj > $point->latitude))
                && ($point->longitude < ($xj - $xi) * ($point->latitude - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
