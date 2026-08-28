<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'name', 'sku', 'quantity', 'unit_price_minor', 'weight_grams', 'notes'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_minor' => MoneyCast::class,
            'weight_grams' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lineTotal(): Money
    {
        return ($this->unit_price_minor ?? Money::zero())->times($this->quantity);
    }
}
