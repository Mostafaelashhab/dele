<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\OfferStatus;
use Database\Factories\DeliveryOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delivery_id', 'delivery_company_id', 'round', 'rank', 'status',
    'quoted_price_minor', 'company_payout_minor', 'currency',
    'quoted_eta_minutes', 'score_bps', 'score_breakdown', 'offered_at', 'expires_at',
])]
class DeliveryOffer extends Model
{
    /** @use HasFactory<DeliveryOfferFactory> */
    use HasFactory, HasUlids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'round' => 'integer',
            'rank' => 'integer',
            'quoted_price_minor' => MoneyCast::class,
            'company_payout_minor' => MoneyCast::class,
            'quoted_eta_minutes' => 'integer',
            'score_bps' => 'integer',
            'score_breakdown' => 'array',
            'offered_at' => 'datetime',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
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
    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', OfferStatus::Pending);
    }

    /**
     * Offers that are still the company's to answer: pending and in date.
     */
    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('status', OfferStatus::Pending)
            ->where('expires_at', '>', now());
    }

    #[Scope]
    protected function overdue(Builder $query): Builder
    {
        return $query->where('status', OfferStatus::Pending)
            ->where('expires_at', '<=', now());
    }

    public function quotedPrice(): Money
    {
        return $this->quoted_price_minor ?? Money::zero();
    }

    public function payout(): Money
    {
        return $this->company_payout_minor ?? Money::zero();
    }

    public function score(): float
    {
        return $this->score_bps / 10000;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAnswerable(): bool
    {
        return $this->status->isOpen() && ! $this->isExpired();
    }

    public function secondsRemaining(): int
    {
        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }
}
