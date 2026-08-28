<?php

namespace App\Models;

use App\Domain\Shared\Concerns\HasMedia;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Enums\DeliveryStatus;
use App\Enums\RiderStatus;
use App\Enums\VehicleType;
use Database\Factories\RiderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'delivery_company_id', 'user_id', 'name', 'phone', 'national_id', 'status',
    'vehicle_type', 'vehicle_identifier', 'max_concurrent_deliveries',
])]
class Rider extends Model
{
    /** @use HasFactory<RiderFactory> */
    use HasFactory, HasMedia, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RiderStatus::class,
            'vehicle_type' => VehicleType::class,
            'max_concurrent_deliveries' => 'integer',
            'active_deliveries_count' => 'integer',
            'rating_bps' => 'integer',
            'acceptance_rate_bps' => 'integer',
            'completion_rate_bps' => 'integer',
            'completed_deliveries_count' => 'integer',
            'current_latitude' => 'float',
            'current_longitude' => 'float',
            'location_updated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'went_online_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DeliveryCompany, $this>
     */
    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * @return HasMany<DeliveryAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }

    /**
     * @return HasMany<DeliveryLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(DeliveryLocation::class);
    }

    #[Scope]
    protected function online(Builder $query): Builder
    {
        return $query->where('status', RiderStatus::Online);
    }

    /**
     * Online, not suspended, and below their configured concurrency cap.
     */
    #[Scope]
    protected function availableForWork(Builder $query): Builder
    {
        return $query->where('status', RiderStatus::Online)
            ->whereColumn('active_deliveries_count', '<', 'max_concurrent_deliveries');
    }

    public function currentLocation(): ?GeoPoint
    {
        return GeoPoint::tryMake($this->current_latitude, $this->current_longitude);
    }

    public function hasCapacity(): bool
    {
        return $this->active_deliveries_count < $this->max_concurrent_deliveries;
    }

    public function canAcceptWork(): bool
    {
        return $this->status->canReceiveWork() && $this->hasCapacity();
    }

    /**
     * @return Collection<int, Delivery>
     */
    public function activeDeliveries(): Collection
    {
        return $this->deliveries()
            ->whereIn('status', array_column(DeliveryStatus::occupiesRider(), 'value'))
            ->orderBy('created_at')
            ->get();
    }

    public function acceptanceRate(): float
    {
        return $this->acceptance_rate_bps / 10000;
    }

    public function completionRate(): float
    {
        return $this->completion_rate_bps / 10000;
    }

    public function rating(): float
    {
        return $this->rating_bps / 1000;
    }

    public function isOnline(): bool
    {
        return $this->status === RiderStatus::Online;
    }
}
