<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\LedgerAccountType;
use App\Enums\SettlementPeriod;
use App\Enums\SettlementStatus;
use Database\Factories\SettlementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference', 'party_type', 'party_id', 'period', 'period_start', 'period_end',
    'status', 'deliveries_count', 'gross_minor', 'platform_fee_minor',
    'cod_collected_minor', 'adjustments_minor', 'net_payable_minor', 'currency',
    'generated_by_user_id', 'notes',
])]
class Settlement extends Model
{
    /** @use HasFactory<SettlementFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'party_type' => LedgerAccountType::class,
            'period' => SettlementPeriod::class,
            'status' => SettlementStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'deliveries_count' => 'integer',
            'gross_minor' => MoneyCast::class,
            'platform_fee_minor' => MoneyCast::class,
            'cod_collected_minor' => MoneyCast::class,
            'adjustments_minor' => MoneyCast::class,
            'net_payable_minor' => MoneyCast::class,
            'locked_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<FinancialTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    #[Scope]
    protected function payable(Builder $query): Builder
    {
        return $query->whereIn('status', [SettlementStatus::Open->value, SettlementStatus::Locked->value]);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function netPayable(): Money
    {
        return $this->net_payable_minor ?? Money::zero();
    }

    public function gross(): Money
    {
        return $this->gross_minor ?? Money::zero();
    }

    /**
     * Resolve the party this settlement pays, without a polymorphic relation:
     * the party type set is small and closed.
     */
    public function party(): DeliveryCompany|Business|Rider|null
    {
        return match ($this->party_type) {
            LedgerAccountType::DeliveryCompany => DeliveryCompany::find($this->party_id),
            LedgerAccountType::Business => Business::find($this->party_id),
            LedgerAccountType::Rider => Rider::find($this->party_id),
            default => null,
        };
    }
}
