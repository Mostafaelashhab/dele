<?php

namespace App\Models;

use App\Domain\Shared\Concerns\HasMedia;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Enums\AccountStatus;
use App\Enums\RiderStatus;
use App\Enums\SettlementPeriod;
use Database\Factories\DeliveryCompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'name', 'name_ar', 'slug', 'contact_person', 'phone', 'email', 'status',
    'address_line', 'latitude', 'longitude', 'provider', 'provider_config',
    'auto_accept_offers', 'max_concurrent_deliveries', 'offer_timeout_seconds',
    'commission_bps', 'settlement_period', 'settlement_account', 'working_hours',
    'is_solo',
])]
class DeliveryCompany extends Model
{
    /** @use HasFactory<DeliveryCompanyFactory> */
    use HasFactory, HasMedia, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'settlement_period' => SettlementPeriod::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'provider_config' => 'array',
            'working_hours' => 'array',
            'is_solo' => 'boolean',
            'auto_accept_offers' => 'boolean',
            'max_concurrent_deliveries' => 'integer',
            'offer_timeout_seconds' => 'integer',
            'commission_bps' => 'integer',
            'rating_bps' => 'integer',
            'acceptance_rate_bps' => 'integer',
            'completion_rate_bps' => 'integer',
            'average_pickup_minutes' => 'integer',
            'completed_deliveries_count' => 'integer',
            'metrics_updated_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Rider, $this>
     */
    public function riders(): HasMany
    {
        return $this->hasMany(Rider::class);
    }

    /**
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->withPivot(['role', 'is_active', 'is_primary_contact', 'job_title'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<CompanyUser, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * @return BelongsToMany<Zone, $this, Pivot>
     */
    public function serviceAreas(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'company_service_areas')
            ->withPivot(['accepts_pickup', 'accepts_dropoff', 'surcharge_minor'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * @return HasMany<DeliveryOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(DeliveryOffer::class);
    }

    /**
     * @return HasMany<PricingRule, $this>
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    /**
     * @return MorphMany<ApiClient, $this>
     */
    public function apiClients(): MorphMany
    {
        return $this->morphMany(ApiClient::class, 'owner');
    }

    /**
     * @return MorphMany<WebhookEndpoint, $this>
     */
    public function webhookEndpoints(): MorphMany
    {
        return $this->morphMany(WebhookEndpoint::class, 'owner');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', AccountStatus::Active);
    }

    #[Scope]
    protected function dispatchable(Builder $query): Builder
    {
        return $query->where('status', AccountStatus::Active)
            ->whereNull('suspended_at');
    }

    /**
     * Companies covering a zone for pickup, used as the first pass of the
     * matching engine's candidate filter.
     */
    #[Scope]
    protected function servingZone(Builder $query, string $zoneId, string $direction = 'pickup'): Builder
    {
        $column = $direction === 'pickup' ? 'accepts_pickup' : 'accepts_dropoff';

        return $query->whereHas('serviceAreas', function (Builder $areas) use ($zoneId, $column): void {
            $areas->where('zones.id', $zoneId)->where("company_service_areas.{$column}", true);
        });
    }

    public function location(): ?GeoPoint
    {
        return GeoPoint::tryMake($this->latitude, $this->longitude);
    }

    public function canOperate(): bool
    {
        return $this->status->canOperate();
    }

    public function offerTimeoutSeconds(): int
    {
        return $this->offer_timeout_seconds ?? (int) config('platform.dispatch.offer_timeout_seconds');
    }

    public function commissionBasisPoints(): int
    {
        return $this->commission_bps ?? (int) config('platform.settlements.company_commission_bps');
    }

    /**
     * Rate expressed as a 0..1 fraction for the matching engine's scorers.
     */
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

    public function availableRiderCount(): int
    {
        return $this->riders()
            ->where('status', RiderStatus::Online)
            ->whereColumn('active_deliveries_count', '<', 'max_concurrent_deliveries')
            ->count();
    }

    /**
     * Whether the company is inside its declared working hours right now.
     * A company with no declared hours is treated as always open.
     */
    public function isWithinWorkingHours(?\DateTimeInterface $moment = null): bool
    {
        if (blank($this->working_hours)) {
            return true;
        }

        /*
         * Working hours are wall-clock times in the city the network runs in,
         * so they have to be read in that city's timezone.
         *
         * Timestamps are stored in UTC, which is right, but comparing a
         * shop-hours schedule against UTC silently shifts every window by the
         * offset — and in Egypt that is three hours. A company open from 13:00
         * was being treated as closed until 16:00 local, so orders placed in
         * that band matched nobody and no offer was ever sent.
         */
        $moment = ($moment ? Carbon::instance($moment) : now())
            ->setTimezone(config('platform.timezone', 'UTC'));

        // The day is read after the conversion too: near midnight the UTC day
        // and the local day are different days of the week.
        $window = $this->working_hours[mb_strtolower($moment->format('l'))] ?? null;

        if (blank($window) || ($window['closed'] ?? false)) {
            return false;
        }

        $time = $moment->format('H:i');
        $opens = $window['opens'] ?? '00:00';
        $closes = $window['closes'] ?? '23:59';

        // A window that closes past midnight wraps around.
        return $closes >= $opens
            ? $time >= $opens && $time <= $closes
            : $time >= $opens || $time <= $closes;
    }

    /**
     * The one rider a solo account is built around.
     *
     * A solo company is a rider working alone, so "the company" and "the
     * rider" are the same person and the interface should say so.
     */
    public function soloRider(): ?Rider
    {
        return $this->is_solo ? $this->riders()->first() : null;
    }

    public function displayName(): string
    {
        return app()->getLocale() === 'ar' && filled($this->name_ar)
            ? $this->name_ar
            : $this->name;
    }
}
