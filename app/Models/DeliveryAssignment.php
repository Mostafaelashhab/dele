<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\AssignmentStatus;
use Database\Factories\DeliveryAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delivery_id', 'rider_id', 'delivery_company_id', 'status',
    'assigned_by_user_id', 'pickup_distance_meters', 'estimated_pickup_minutes',
    'payout_minor', 'currency', 'offered_at', 'expires_at',
])]
class DeliveryAssignment extends Model
{
    /** @use HasFactory<DeliveryAssignmentFactory> */
    use HasFactory, HasUlids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'offered',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'pickup_distance_meters' => 'integer',
            'estimated_pickup_minutes' => 'integer',
            'payout_minor' => MoneyCast::class,
            'offered_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<Rider, $this>
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
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
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AssignmentStatus::Offered->value,
            AssignmentStatus::Accepted->value,
        ]);
    }

    #[Scope]
    protected function awaitingRider(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::Offered);
    }

    public function payout(): Money
    {
        return $this->payout_minor ?? Money::zero();
    }

    public function isAnswerable(): bool
    {
        return $this->status === AssignmentStatus::Offered
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function secondsRemaining(): int
    {
        if ($this->expires_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }
}
