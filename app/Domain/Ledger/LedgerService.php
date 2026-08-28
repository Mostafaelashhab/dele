<?php

namespace App\Domain\Ledger;

use App\Domain\Shared\ValueObjects\Money;
use App\Enums\EntryType;
use App\Enums\LedgerAccountType;
use App\Models\Delivery;
use App\Models\FinancialTransaction;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Posts groups of ledger entries and derives balances from them.
 *
 * A posting is refused unless its debits and credits cancel exactly, so the
 * ledger cannot drift: any bug in a financial calculation surfaces as a
 * failed posting rather than as money quietly appearing or vanishing.
 */
class LedgerService
{
    /**
     * Post a balanced group atomically.
     *
     * @param  array<int, LedgerEntry>  $entries
     * @return Collection<int, FinancialTransaction>
     *
     * @throws UnbalancedPosting
     */
    public function post(
        array $entries,
        ?Order $order = null,
        ?Delivery $delivery = null,
        ?\DateTimeInterface $occurredAt = null,
        ?string $groupId = null,
    ): Collection {
        $this->assertBalanced($entries);

        $groupId ??= (string) Str::ulid();
        $occurredAt ??= now();

        return DB::transaction(function () use ($entries, $order, $delivery, $occurredAt, $groupId): Collection {
            return collect($entries)->map(fn (LedgerEntry $entry) => FinancialTransaction::create([
                'group_id' => $groupId,
                'account_type' => $entry->accountType,
                'account_id' => $entry->accountId,
                'entry_type' => $entry->entryType,
                'category' => $entry->category,
                'amount_minor' => $entry->amount->absolute(),
                'currency' => $entry->amount->currency,
                'order_id' => $order?->id,
                'delivery_id' => $delivery?->id,
                'description' => $entry->description,
                'metadata' => $entry->metadata === [] ? null : $entry->metadata,
                'occurred_at' => $occurredAt,
            ]));
        });
    }

    /**
     * Current balance of an account, derived by summing its entries. There is
     * no stored balance to fall out of step with the entries that produced it.
     */
    public function balance(LedgerAccountType $type, ?string $accountId): Money
    {
        $row = FinancialTransaction::query()
            ->forAccount($type, $accountId)
            ->selectRaw(
                'SUM(CASE WHEN entry_type = ? THEN amount_minor ELSE -amount_minor END) AS balance',
                [EntryType::Credit->value]
            )
            ->value('balance');

        return Money::ofMinor((int) ($row ?? 0));
    }

    /**
     * Balance of the entries not yet rolled into a settlement — what the
     * platform actually owes this party right now.
     */
    public function unsettledBalance(LedgerAccountType $type, ?string $accountId): Money
    {
        $row = FinancialTransaction::query()
            ->forAccount($type, $accountId)
            ->unsettled()
            ->selectRaw(
                'SUM(CASE WHEN entry_type = ? THEN amount_minor ELSE -amount_minor END) AS balance',
                [EntryType::Credit->value]
            )
            ->value('balance');

        return Money::ofMinor((int) ($row ?? 0));
    }

    /**
     * @param  array<int, LedgerEntry>  $entries
     *
     * @throws UnbalancedPosting
     */
    protected function assertBalanced(array $entries): void
    {
        $difference = collect($entries)->sum(fn (LedgerEntry $entry) => $entry->signedMinor());

        if ($difference !== 0) {
            throw UnbalancedPosting::by((int) $difference);
        }
    }
}
