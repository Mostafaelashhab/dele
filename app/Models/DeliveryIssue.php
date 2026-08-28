<?php

namespace App\Models;

use App\Enums\DeliveryIssueCategory;
use App\Enums\DeliveryIssueStatus;
use App\Enums\DeliveryStatus;
use Database\Factories\DeliveryIssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A problem reported by the person waiting for the delivery.
 */
#[Fillable([
    'order_id', 'delivery_id', 'delivery_company_id', 'rider_id',
    'category', 'status', 'note', 'delivery_status', 'reporter_ip',
    'resolved_by_user_id', 'resolution_note', 'acknowledged_at', 'resolved_at',
])]
class DeliveryIssue extends Model
{
    /** @use HasFactory<DeliveryIssueFactory> */
    use HasFactory, HasUlids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DeliveryIssueCategory::class,
            'status' => DeliveryIssueStatus::class,
            'delivery_status' => DeliveryStatus::class,
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function unresolved(Builder $query): void
    {
        $query->whereIn('status', [
            DeliveryIssueStatus::Open->value,
            DeliveryIssueStatus::Acknowledged->value,
        ]);
    }

    public function isResolved(): bool
    {
        return $this->status->isClosed();
    }
}
