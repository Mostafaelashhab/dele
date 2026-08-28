<?php

namespace Tests\Feature;

use App\Actions\Settlements\GenerateSettlementsAction;
use App\Domain\Ledger\LedgerEntry;
use App\Domain\Ledger\LedgerService;
use App\Domain\Ledger\UnbalancedPosting;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\EntryType;
use App\Enums\LedgerAccountType;
use App\Enums\SettlementStatus;
use App\Enums\TransactionCategory;
use App\Models\DeliveryCompany;
use App\Models\FinancialTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * The ledger is the platform's account of other people's money, so the
 * properties tested here are absolutes rather than preferences: postings
 * balance, entries are immutable, and a balance is always derived from the
 * entries that produced it.
 */
class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);
    }

    #[Test]
    public function it_posts_a_balanced_group(): void
    {
        $company = DeliveryCompany::factory()->create();

        $entries = $this->ledger->post([
            LedgerEntry::debit(
                LedgerAccountType::Business,
                'biz-1',
                TransactionCategory::BusinessCharge,
                Money::ofMinor(2500),
            ),
            LedgerEntry::credit(
                LedgerAccountType::Platform,
                null,
                TransactionCategory::PlatformFee,
                Money::ofMinor(300),
            ),
            LedgerEntry::credit(
                LedgerAccountType::DeliveryCompany,
                $company->id,
                TransactionCategory::CompanyPayout,
                Money::ofMinor(2200),
            ),
        ]);

        $this->assertCount(3, $entries);

        // All three sides share one group, which is what makes the posting
        // reconstructable later.
        $this->assertCount(1, $entries->pluck('group_id')->unique());
    }

    #[Test]
    public function an_unbalanced_posting_is_refused_outright(): void
    {
        // A bug in a fee calculation must surface as a failed posting, never
        // as money quietly appearing.
        $this->expectException(UnbalancedPosting::class);

        $this->ledger->post([
            LedgerEntry::debit(
                LedgerAccountType::Business,
                'biz-1',
                TransactionCategory::BusinessCharge,
                Money::ofMinor(2500),
            ),
            LedgerEntry::credit(
                LedgerAccountType::Platform,
                null,
                TransactionCategory::PlatformFee,
                Money::ofMinor(300),
            ),
        ]);
    }

    #[Test]
    public function nothing_is_written_when_a_posting_is_refused(): void
    {
        try {
            $this->ledger->post([
                LedgerEntry::debit(
                    LedgerAccountType::Business,
                    'biz-1',
                    TransactionCategory::BusinessCharge,
                    Money::ofMinor(100),
                ),
            ]);
        } catch (UnbalancedPosting) {
            // expected
        }

        $this->assertSame(0, FinancialTransaction::query()->count());
    }

    #[Test]
    public function a_posted_entry_can_never_be_edited(): void
    {
        $entry = $this->postSimplePair()->first();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $entry->update(['amount_minor' => 999999]);
    }

    #[Test]
    public function a_posted_entry_can_never_be_deleted(): void
    {
        $entry = $this->postSimplePair()->first();

        $this->expectException(RuntimeException::class);

        $entry->delete();
    }

    #[Test]
    public function a_correction_is_made_by_posting_the_reverse(): void
    {
        $company = DeliveryCompany::factory()->create();

        $this->ledger->post([
            LedgerEntry::credit(
                LedgerAccountType::DeliveryCompany,
                $company->id,
                TransactionCategory::CompanyPayout,
                Money::ofMinor(5000),
            ),
            LedgerEntry::debit(
                LedgerAccountType::Platform,
                null,
                TransactionCategory::CompanyPayout,
                Money::ofMinor(5000),
            ),
        ]);

        $this->assertSame(
            5000,
            $this->ledger->balance(LedgerAccountType::DeliveryCompany, $company->id)->minor,
        );

        // The payout was wrong; the fix is an offsetting entry, not an edit.
        $this->ledger->post([
            LedgerEntry::debit(
                LedgerAccountType::DeliveryCompany,
                $company->id,
                TransactionCategory::Adjustment,
                Money::ofMinor(1500),
            ),
            LedgerEntry::credit(
                LedgerAccountType::Platform,
                null,
                TransactionCategory::Adjustment,
                Money::ofMinor(1500),
            ),
        ]);

        $this->assertSame(
            3500,
            $this->ledger->balance(LedgerAccountType::DeliveryCompany, $company->id)->minor,
        );

        // Both the original and the correction remain on the record.
        $this->assertSame(2, FinancialTransaction::query()
            ->forAccount(LedgerAccountType::DeliveryCompany, $company->id)
            ->count());
    }

    #[Test]
    public function the_whole_ledger_always_nets_to_zero(): void
    {
        $company = DeliveryCompany::factory()->create();

        foreach ([2500, 1800, 4200] as $amount) {
            $fee = (int) round($amount * 0.12);

            $this->ledger->post([
                LedgerEntry::debit(LedgerAccountType::Business, 'biz-1', TransactionCategory::BusinessCharge, Money::ofMinor($amount)),
                LedgerEntry::credit(LedgerAccountType::Platform, null, TransactionCategory::PlatformFee, Money::ofMinor($fee)),
                LedgerEntry::credit(LedgerAccountType::DeliveryCompany, $company->id, TransactionCategory::CompanyPayout, Money::ofMinor($amount - $fee)),
            ]);
        }

        $net = FinancialTransaction::query()->get()->sum(
            fn (FinancialTransaction $entry) => $entry->entry_type === EntryType::Credit
                ? $entry->amount()->minor
                : -$entry->amount()->minor
        );

        $this->assertSame(0, $net);
    }

    #[Test]
    public function unsettled_balance_excludes_what_has_already_been_settled(): void
    {
        $company = DeliveryCompany::factory()->create();

        $this->creditCompany($company, 3000);
        $this->creditCompany($company, 2000);

        $this->assertSame(5000, $this->ledger->unsettledBalance(LedgerAccountType::DeliveryCompany, $company->id)->minor);

        $settlements = app(GenerateSettlementsAction::class)->handle(
            now()->subDay(),
            now()->addDay(),
        );

        $this->assertCount(1, $settlements);

        $settlement = $settlements->first();

        $this->assertSame(5000, $settlement->netPayable()->minor);
        $this->assertSame(SettlementStatus::Open, $settlement->status);

        // Everything in the period is now attached to the statement, so the
        // unsettled balance falls to zero while the lifetime balance does not.
        $this->assertSame(0, $this->ledger->unsettledBalance(LedgerAccountType::DeliveryCompany, $company->id)->minor);
        $this->assertSame(5000, $this->ledger->balance(LedgerAccountType::DeliveryCompany, $company->id)->minor);
    }

    #[Test]
    public function paying_a_settlement_returns_the_partys_balance_to_zero(): void
    {
        $company = DeliveryCompany::factory()->create();

        $this->creditCompany($company, 7500);

        $settlement = app(GenerateSettlementsAction::class)
            ->handle(now()->subDay(), now()->addDay())
            ->first();

        app(GenerateSettlementsAction::class)->markPaid($settlement, null, 'BANK-REF-1');

        $settlement->refresh();

        $this->assertSame(SettlementStatus::Paid, $settlement->status);
        $this->assertNotNull($settlement->paid_at);
        $this->assertSame('BANK-REF-1', $settlement->payment_reference);

        // The money has changed hands, so the platform no longer owes it.
        $this->assertSame(
            0,
            $this->ledger->balance(LedgerAccountType::DeliveryCompany, $company->id)->minor,
        );
    }

    #[Test]
    public function paying_a_settlement_twice_does_not_pay_twice(): void
    {
        $company = DeliveryCompany::factory()->create();

        $this->creditCompany($company, 4000);

        $action = app(GenerateSettlementsAction::class);
        $settlement = $action->handle(now()->subDay(), now()->addDay())->first();

        $action->markPaid($settlement);
        $action->markPaid($settlement->fresh());

        $this->assertSame(
            0,
            $this->ledger->balance(LedgerAccountType::DeliveryCompany, $company->id)->minor,
        );
    }

    #[Test]
    public function a_period_with_no_activity_produces_no_settlement(): void
    {
        $settlements = app(GenerateSettlementsAction::class)->handle(
            now()->subYear(),
            now()->subYear()->addDay(),
        );

        $this->assertTrue($settlements->isEmpty());
    }

    /**
     * @return Collection<int, FinancialTransaction>
     */
    private function postSimplePair(): Collection
    {
        return $this->ledger->post([
            LedgerEntry::debit(LedgerAccountType::Business, 'biz-1', TransactionCategory::BusinessCharge, Money::ofMinor(1000)),
            LedgerEntry::credit(LedgerAccountType::Platform, null, TransactionCategory::PlatformFee, Money::ofMinor(1000)),
        ]);
    }

    private function creditCompany(DeliveryCompany $company, int $minor): void
    {
        $this->ledger->post([
            LedgerEntry::credit(LedgerAccountType::DeliveryCompany, $company->id, TransactionCategory::CompanyPayout, Money::ofMinor($minor)),
            LedgerEntry::debit(LedgerAccountType::Platform, null, TransactionCategory::CompanyPayout, Money::ofMinor($minor)),
        ]);
    }
}
