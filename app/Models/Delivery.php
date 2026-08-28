<?php

namespace App\Models;

use App\Domain\Proof\DeliveryConfirmationCode;
use App\Domain\Shared\Concerns\HasMedia;
use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryStatus;
use Database\Factories\DeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One fulfilment attempt: who is carrying the order, for how much, and how
 * far along they are. The status column is written only by the delivery state
 * machine, never assigned directly.
 */
#[Fillable([
    'order_id', 'business_id', 'delivery_company_id', 'rider_id', 'public_id',
    'tracking_token', 'status', 'attempt', 'provider', 'provider_reference',
    'distance_meters', 'estimated_minutes', 'estimated_delivery_at', 'currency',
    'price_minor', 'platform_fee_minor', 'company_payout_minor',
    'rider_payout_minor', 'price_breakdown', 'matching_snapshot',
])]
class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use HasFactory, HasMedia, HasUlids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'attempt' => 1,
        'provider' => 'internal',
        // Mirrors the column default so a freshly created row can be read
        // under Model::shouldBeStrict() without a refetch.
        'confirmation_attempts' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $delivery): void {
            $delivery->public_id ??= 'del_'.Str::lower((string) Str::ulid());
            $delivery->tracking_token ??= self::generateTrackingToken();
            // Issued here rather than at handover time so the recipient can
            // see it on their tracking page well before the rider arrives.
            $delivery->confirmation_code ??= DeliveryConfirmationCode::generate();
        });
    }

    /**
     * Was this delivery closed with evidence attached?
     *
     * Either form counts: a photograph of where the parcel was left, or the
     * recipient's code read back by the rider.
     */
    public function hasProofOfDelivery(): bool
    {
        return $this->confirmation_code_verified_at !== null
            || filled($this->proof_photo_path);
    }

    /**
     * Is the code still a usable way to close this delivery?
     */
    public function confirmationCodeAvailable(): bool
    {
        return $this->confirmation_code !== null
            && $this->confirmation_code_verified_at === null
            && $this->confirmation_attempts < DeliveryConfirmationCode::maxAttempts();
    }

    public function confirmationAttemptsLeft(): int
    {
        return max(0, DeliveryConfirmationCode::maxAttempts() - $this->confirmation_attempts);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'attempt' => 'integer',
            'distance_meters' => 'integer',
            'estimated_minutes' => 'integer',
            'price_minor' => MoneyCast::class,
            'platform_fee_minor' => MoneyCast::class,
            'company_payout_minor' => MoneyCast::class,
            'rider_payout_minor' => MoneyCast::class,
            'cod_collected_minor' => MoneyCast::class,
            'price_breakdown' => 'array',
            'matching_snapshot' => 'array',
            'dispatch_round' => 'integer',
            'offers_sent_count' => 'integer',
            'financials_recorded' => 'boolean',
            'confirmation_attempts' => 'integer',
            'confirmation_code_verified_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'searching_at' => 'datetime',
            'accepted_at' => 'datetime',
            'assigned_at' => 'datetime',
            'arrived_at_pickup_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'in_transit_at' => 'datetime',
            'arrived_at_destination_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * A URL-safe, high-entropy token. Tracking pages are unauthenticated, so
     * the token is the only thing standing between a stranger and a customer's
     * address — it must never be derived from an id.
     */
    public static function generateTrackingToken(): string
    {
        return Str::lower(Str::random(8)).bin2hex(random_bytes(
            (int) config('platform.tracking.token_bytes', 24)
        ));
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<DeliveryCompany, $this>
     */
    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    /**
     * @return BelongsTo<Rider, $this>
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    /**
     * @return HasMany<DeliveryOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(DeliveryOffer::class);
    }

    /**
     * @return HasOne<DeliveryOffer, $this>
     */
    public function acceptedOffer(): HasOne
    {
        return $this->hasOne(DeliveryOffer::class)->where('status', 'accepted');
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
    protected function active(Builder $query): Builder
    {
        return $query->whereIn('status', array_column(DeliveryStatus::active(), 'value'));
    }

    #[Scope]
    protected function awaitingDispatch(Builder $query): Builder
    {
        return $query->whereIn('status', [DeliveryStatus::Pending->value, DeliveryStatus::Searching->value]);
    }

    #[Scope]
    protected function forCompany(Builder $query, DeliveryCompany|string $company): Builder
    {
        return $query->where(
            'delivery_company_id',
            $company instanceof DeliveryCompany ? $company->id : $company
        );
    }

    #[Scope]
    protected function deliveredBetween(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->where('status', DeliveryStatus::Delivered)
            ->whereBetween('delivered_at', [$from, $to]);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function price(): Money
    {
        return $this->price_minor ?? Money::zero();
    }

    public function platformFee(): Money
    {
        return $this->platform_fee_minor ?? Money::zero();
    }

    public function companyPayout(): Money
    {
        return $this->company_payout_minor ?? Money::zero();
    }

    public function riderPayout(): Money
    {
        return $this->rider_payout_minor ?? Money::zero();
    }

    public function trackingUrl(): string
    {
        return route('tracking.show', ['token' => $this->tracking_token]);
    }

    public function isAssignable(): bool
    {
        return $this->status === DeliveryStatus::Accepted;
    }

    public function isCancellable(): bool
    {
        return ! $this->status->isTerminal();
    }

    /**
     * Wall-clock minutes from the business placing the order to hand-off.
     * Null until the delivery actually completes.
     */
    public function totalMinutes(): ?int
    {
        if (! $this->delivered_at) {
            return null;
        }

        return (int) $this->created_at->diffInMinutes($this->delivered_at);
    }

    public function pickupMinutes(): ?int
    {
        if (! $this->picked_up_at || ! $this->accepted_at) {
            return null;
        }

        return (int) $this->accepted_at->diffInMinutes($this->picked_up_at);
    }

    /**
     * Current best estimate of arrival, recomputed from whichever milestone
     * the delivery has actually reached.
     */
    public function estimatedArrival(): ?Carbon
    {
        if ($this->delivered_at) {
            return $this->delivered_at;
        }

        return $this->estimated_delivery_at;
    }

    public function isLate(): bool
    {
        $eta = $this->estimatedArrival();

        return $eta !== null
            && ! $this->status->isTerminal()
            && $eta->isPast();
    }
}
