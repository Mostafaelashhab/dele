<?php

namespace App\Models;

use App\Domain\Shared\Concerns\HasMedia;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Enums\AccountStatus;
use App\Enums\DeliveryPriority;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'name_ar', 'slug', 'legal_name', 'category', 'contact_person',
    'phone', 'email', 'status', 'default_zone_id', 'address_line', 'latitude',
    'longitude', 'platform_fee_bps', 'default_priority', 'matching_strategy',
    'credit_limit_minor', 'api_enabled', 'settings',
    'is_individual', ])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasMedia, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_individual' => 'boolean',
            'status' => AccountStatus::class,
            'default_priority' => DeliveryPriority::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'platform_fee_bps' => 'integer',
            'credit_limit_minor' => 'integer',
            'api_enabled' => 'boolean',
            'settings' => 'array',
            'onboarded_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function defaultZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'default_zone_id');
    }

    /**
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_users')
            ->withPivot(['role', 'is_active', 'is_primary_contact', 'job_title'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<BusinessUser, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return MorphMany<Address, $this>
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'owner');
    }

    /**
     * @return HasMany<BusinessCompanyPreference, $this>
     */
    public function companyPreferences(): HasMany
    {
        return $this->hasMany(BusinessCompanyPreference::class);
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

    public function location(): ?GeoPoint
    {
        return GeoPoint::tryMake($this->latitude, $this->longitude);
    }

    public function canOperate(): bool
    {
        return $this->status->canOperate();
    }

    public function platformFeeBasisPoints(): int
    {
        return $this->platform_fee_bps ?? (int) config('platform.pricing.platform_fee.percentage_bps');
    }

    /**
     * The glyph that stands for this business's trade.
     *
     * A shop owner spots their own category far faster from a symbol than
     * from a word in a table cell, so the icon is part of the record rather
     * than a decision each view makes for itself.
     */
    public function categoryIcon(): string
    {
        return match ($this->category) {
            'restaurant' => 'restaurant',
            'pharmacy' => 'pharmacy',
            'grocery' => 'grocery',
            'clothing' => 'clothing',
            'electronics' => 'electronics',
            'online' => 'online',
            default => 'store',
        };
    }

    public function categoryLabel(): string
    {
        return __('business.category.'.$this->category);
    }

    public function displayName(): string
    {
        return app()->getLocale() === 'ar' && filled($this->name_ar)
            ? $this->name_ar
            : $this->name;
    }
}
