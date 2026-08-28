<?php

namespace App\Models;

use App\Domain\Shared\Support\LocationSnapshotCast;
use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Enums\DeliveryPriority;
use App\Enums\OrderStatus;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * What the business asked for. The logistics of carrying it out live on
 * Delivery — an order may need more than one attempt.
 */
#[Fillable([
    'business_id', 'customer_id', 'created_by_user_id', 'api_client_id',
    'reference', 'number', 'status', 'pickup', 'dropoff', 'pickup_zone_id',
    'dropoff_zone_id', 'priority', 'package_size', 'package_weight_grams',
    'payment_type', 'cod_amount_minor', 'declared_value_minor', 'currency',
    'notes', 'scheduled_for', 'metadata',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'priority' => 'standard',
        'package_size' => 'small',
        'payment_type' => 'prepaid',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'priority' => DeliveryPriority::class,
            'package_size' => PackageSize::class,
            'payment_type' => PaymentType::class,
            'pickup' => LocationSnapshotCast::class,
            'dropoff' => LocationSnapshotCast::class,
            'cod_amount_minor' => MoneyCast::class,
            'declared_value_minor' => MoneyCast::class,
            'package_weight_grams' => 'integer',
            'delivery_attempts' => 'integer',
            'metadata' => 'array',
            'scheduled_for' => 'datetime',
            'placed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function pickupZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'pickup_zone_id');
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function dropoffZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'dropoff_zone_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * The delivery currently representing this order — the latest attempt.
     *
     * @return HasOne<Delivery, $this>
     */
    public function currentDelivery(): HasOne
    {
        return $this->hasOne(Delivery::class)->ofMany('attempt', 'max');
    }

    /**
     * @return HasMany<OrderEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    /**
     * @return HasMany<FinancialTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    #[Scope]
    protected function forBusiness(Builder $query, Business|string $business): Builder
    {
        return $query->where('business_id', $business instanceof Business ? $business->id : $business);
    }

    #[Scope]
    protected function withStatus(Builder $query, OrderStatus ...$statuses): Builder
    {
        return $query->whereIn('status', array_column($statuses, 'value'));
    }

    #[Scope]
    protected function placedBetween(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function pickupSnapshot(): LocationSnapshot
    {
        return $this->pickup;
    }

    public function dropoffSnapshot(): LocationSnapshot
    {
        return $this->dropoff;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [OrderStatus::Draft, OrderStatus::Pending, OrderStatus::Active], true);
    }
}
