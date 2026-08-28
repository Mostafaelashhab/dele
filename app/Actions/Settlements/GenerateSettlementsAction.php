<?php

namespace App\Actions\Settlements;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Domain\Ledger\LedgerEntry;
use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\AuditAction;
use App\Enums\EntryType;
use App\Enums\LedgerAccountType;
use App\Enums\SettlementStatus;
use App\Enums\TransactionCategory;
use App\Models\FinancialTransaction;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rolls unsettled ledger entries into per-party statements.
 *
 * Generating a settlement does not compute new money; it groups entries that
 * already exist and stamps them with the settlement's id. That is what makes
 * a statement reproducible: the same entries would always produce the same
 * totals, and nothing is recalculated at payout time.
 */
class GenerateSettlementsAction
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, Settlement>
     */
    public function handle(Carbon $from, Carbon $to, ?User $generatedBy = null): Collection
    {
        // Only parties the platform actually pays out to. A business is billed
        // rather than settled, so it is handled by invoicing, not here.
        $parties = FinancialTransaction::query()
            ->unsettled()
            ->occurredBetween($from, $to)
            ->whereIn('account_type', [
                LedgerAccountType::DeliveryCompany->value,
                LedgerAccountType::Rider->value,
            ])
            ->whereNotNull('account_id')
            ->select('account_type', 'account_id')
            ->distinct()
            ->get();

        return $parties
            ->map(fn (FinancialTransaction $party) => $this->settleParty(
                $party->account_type,
                $party->account_id,
                $from,
                $to,
                $generatedBy,
            ))
            ->filter()
            ->values();
    }

    protected function settleParty(
        LedgerAccountType $type,
        string $accountId,
        Carbon $from,
        Carbon $to,
        ?User $generatedBy,
    ): ?Settlement {
        return DB::transaction(function () use ($type, $accountId, $from, $to, $generatedBy): ?Settlement {
            $entries = FinancialTransaction::query()
                ->forAccount($type, $accountId)
                ->unsettled()
                ->occurredBetween($from, $to)
                ->lockForUpdate()
                ->get();

            if ($entries->isEmpty()) {
                return null;
            }

            $credits = $entries->where('entry_type', EntryType::Credit);
            $debits = $entries->where('entry_type', EntryType::Debit);

            $gross = Money::ofMinor((int) $credits->sum(fn (FinancialTransaction $t) => $t->amount()->minor));
            $deductions = Money::ofMinor((int) $debits->sum(fn (FinancialTransaction $t) => $t->amount()->minor));

            $settlement = Settlement::create([
                'reference' => $this->reference($type),
                'party_type' => $type,
                'party_id' => $accountId,
                'period' => config('platform.settlements.default_period'),
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'status' => SettlementStatus::Open,
                'deliveries_count' => $entries->pluck('delivery_id')->filter()->unique()->count(),
                'gross_minor' => $gross,
                'platform_fee_minor' => Money::zero(),
                'cod_collected_minor' => Money::ofMinor((int) $entries
                    ->where('category', TransactionCategory::CodCollected)
                    ->sum(fn (FinancialTransaction $t) => $t->amount()->minor)),
                'adjustments_minor' => $deductions->negated(),
                'net_payable_minor' => $gross->minus($deductions),
                'currency' => config('platform.currency.code'),
                'generated_by_user_id' => $generatedBy?->id,
            ]);

            // Stamping the entries is what makes them settled; the ledger rows
            // themselves are otherwise untouched.
            FinancialTransaction::query()
                ->whereIn('id', $entries->pluck('id'))
                ->update(['settlement_id' => $settlement->id]);

            $this->auditLogger->log(
                action: AuditAction::SettlementCreated,
                entity: $settlement,
                actor: $generatedBy ? Actor::user($generatedBy) : Actor::system('settlements'),
                description: __('audit.description.settlement_created', [
                    'reference' => $settlement->reference,
                ]),
                context: ['entries' => $entries->count()],
            );

            return $settlement;
        });
    }

    /**
     * Records the payout and closes the statement.
     */
    public function markPaid(Settlement $settlement, ?User $paidBy = null, ?string $paymentReference = null): Settlement
    {
        if ($settlement->status === SettlementStatus::Paid) {
            return $settlement;
        }

        DB::transaction(function () use ($settlement, $paidBy, $paymentReference): void {
            $settlement->forceFill([
                'status' => SettlementStatus::Paid,
                'locked_at' => $settlement->locked_at ?? now(),
                'paid_at' => now(),
                'payment_reference' => $paymentReference,
            ])->save();

            $payable = $settlement->netPayable();

            // The payout itself is a ledger event: the party's balance returns
            // to zero because the platform has handed the money over.
            if ($payable->isPositive()) {
                $this->ledger->post([
                    LedgerEntry::debit(
                        $settlement->party_type,
                        $settlement->party_id,
                        TransactionCategory::CompanyPayout,
                        $payable,
                        __('finance.description.settlement_payout', ['reference' => $settlement->reference]),
                    ),
                    LedgerEntry::credit(
                        LedgerAccountType::Platform,
                        null,
                        TransactionCategory::CompanyPayout,
                        $payable,
                        __('finance.description.settlement_payout', ['reference' => $settlement->reference]),
                    ),
                ]);
            }

            $this->auditLogger->log(
                action: AuditAction::SettlementPaid,
                entity: $settlement,
                actor: $paidBy ? Actor::user($paidBy) : Actor::system('settlements'),
                context: ['amount_minor' => $payable->minor],
            );
        });

        return $settlement->refresh();
    }

    private function reference(LedgerAccountType $type): string
    {
        $prefix = $type === LedgerAccountType::Rider ? 'RDR' : 'CMP';

        return $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }
}
