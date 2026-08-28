<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\EntryType;
use App\Enums\LedgerAccountType;
use App\Enums\TransactionCategory;
use Database\Factories\FinancialTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * One side of one ledger entry. Immutable by construction: any attempt to
 * update or delete a persisted row throws, so corrections must be posted as
 * new, offsetting entries and the history stays auditable.
 */
#[Fillable([
    'group_id', 'account_type', 'account_id', 'entry_type', 'category',
    'amount_minor', 'currency', 'order_id', 'delivery_id', 'settlement_id',
    'description', 'metadata', 'occurred_at',
])]
class FinancialTransaction extends Model
{
    /** @use HasFactory<FinancialTransactionFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new RuntimeException(
                'Ledger entries are immutable. Post an offsetting adjustment instead of editing.'
            );
        });

        static::deleting(function (): never {
            throw new RuntimeException(
                'Ledger entries cannot be deleted. Post a reversing entry instead.'
            );
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => LedgerAccountType::class,
            'entry_type' => EntryType::class,
            'category' => TransactionCategory::class,
            'amount_minor' => MoneyCast::class,
            'metadata' => 'array',
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

    /**
     * @return BelongsTo<Settlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    #[Scope]
    protected function forAccount(Builder $query, LedgerAccountType $type, ?string $id): Builder
    {
        return $query->where('account_type', $type)->where('account_id', $id);
    }

    #[Scope]
    protected function unsettled(Builder $query): Builder
    {
        return $query->whereNull('settlement_id');
    }

    #[Scope]
    protected function occurredBetween(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    public function amount(): Money
    {
        return $this->amount_minor ?? Money::zero();
    }

    /**
     * The amount as it moves the account balance: credits add, debits subtract.
     */
    public function signedAmount(): Money
    {
        return $this->entry_type === EntryType::Credit
            ? $this->amount()
            : $this->amount()->negated();
    }
}
