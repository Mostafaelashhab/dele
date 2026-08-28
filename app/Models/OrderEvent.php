<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use Database\Factories\OrderEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only. Nothing in the application updates or deletes these rows, so
 * the model exposes no mutators beyond creation.
 */
#[Fillable([
    'order_id', 'delivery_id', 'type', 'from_status', 'to_status', 'actor_type',
    'actor_id', 'actor_label', 'payload', 'is_customer_visible', 'occurred_at',
])]
class OrderEvent extends Model
{
    /** @use HasFactory<OrderEventFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrderEventType::class,
            'from_status' => DeliveryStatus::class,
            'to_status' => DeliveryStatus::class,
            'payload' => 'array',
            'is_customer_visible' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    #[Scope]
    protected function customerVisible(Builder $query): Builder
    {
        return $query->where('is_customer_visible', true);
    }

    #[Scope]
    protected function chronological(Builder $query): Builder
    {
        return $query->orderBy('occurred_at')->orderBy('id');
    }
}
