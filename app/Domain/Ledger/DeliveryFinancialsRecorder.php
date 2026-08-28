<?php

namespace App\Domain\Ledger;

use App\Domain\Shared\ValueObjects\Money;
use App\Enums\LedgerAccountType;
use App\Enums\TransactionCategory;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;

/**
 * Translates a completed delivery into ledger postings.
 *
 * Sign convention: a credit means the platform owes that account; a debit
 * means the account owes the platform. Every group balances to zero, which is
 * checked by the LedgerService before anything is written.
 */
class DeliveryFinancialsRecorder
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    /**
     * Post the revenue split, and the cash-on-delivery leg when there is one.
     *
     * Guarded by a flag on the delivery and a row lock, so a retried job or a
     * duplicated event cannot bill a business twice.
     */
    public function record(Delivery $delivery): bool
    {
        $shouldPost = DB::transaction(function () use ($delivery): bool {
            $locked = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->first();

            if ($locked === null || $locked->financials_recorded) {
                return false;
            }

            $locked->forceFill(['financials_recorded' => true])->save();

            return true;
        });

        if (! $shouldPost) {
            return false;
        }

        $delivery->loadMissing(['order', 'deliveryCompany', 'rider']);

        $this->postDeliveryRevenue($delivery);
        $this->postRiderEarning($delivery);
        $this->postCashOnDelivery($delivery);

        return true;
    }

    /**
     * The business is charged the full price; the platform keeps its fee and
     * the company is owed the remainder.
     */
    protected function postDeliveryRevenue(Delivery $delivery): void
    {
        $price = $delivery->price();

        if ($price->isZero()) {
            return;
        }

        $platformFee = $delivery->platformFee();
        $companyPayout = $price->minus($platformFee);

        $entries = [
            LedgerEntry::debit(
                LedgerAccountType::Business,
                $delivery->business_id,
                TransactionCategory::BusinessCharge,
                $price,
                __('finance.description.business_charge', ['order' => $delivery->order->number]),
                ['delivery_public_id' => $delivery->public_id],
            ),
            LedgerEntry::credit(
                LedgerAccountType::Platform,
                null,
                TransactionCategory::PlatformFee,
                $platformFee,
                __('finance.description.platform_fee', ['order' => $delivery->order->number]),
            ),
            LedgerEntry::credit(
                LedgerAccountType::DeliveryCompany,
                $delivery->delivery_company_id,
                TransactionCategory::CompanyPayout,
                $companyPayout,
                __('finance.description.company_payout', ['order' => $delivery->order->number]),
            ),
        ];

        $this->ledger->post($entries, $delivery->order, $delivery, $delivery->delivered_at);
    }

    /**
     * The rider's share is an obligation of their company, not of the
     * platform, so it posts as its own balanced pair between the two.
     */
    protected function postRiderEarning(Delivery $delivery): void
    {
        $payout = $delivery->riderPayout();

        if ($payout->isZero() || $delivery->rider_id === null) {
            return;
        }

        $this->ledger->post([
            LedgerEntry::debit(
                LedgerAccountType::DeliveryCompany,
                $delivery->delivery_company_id,
                TransactionCategory::RiderPayout,
                $payout,
                __('finance.description.rider_payout', ['order' => $delivery->order->number]),
            ),
            LedgerEntry::credit(
                LedgerAccountType::Rider,
                $delivery->rider_id,
                TransactionCategory::RiderPayout,
                $payout,
                __('finance.description.rider_earning', ['order' => $delivery->order->number]),
            ),
        ], $delivery->order, $delivery, $delivery->delivered_at);
    }

    /**
     * Cash collected at the door belongs to the business. Until it is remitted
     * the company is holding it, which is exactly what these entries say.
     */
    protected function postCashOnDelivery(Delivery $delivery): void
    {
        $collected = $delivery->cod_collected_minor ?? Money::zero();

        if ($collected->isZero()) {
            return;
        }

        $this->ledger->post([
            LedgerEntry::debit(
                LedgerAccountType::DeliveryCompany,
                $delivery->delivery_company_id,
                TransactionCategory::CodCollected,
                $collected,
                __('finance.description.cod_held', ['order' => $delivery->order->number]),
            ),
            LedgerEntry::credit(
                LedgerAccountType::Business,
                $delivery->business_id,
                TransactionCategory::CodCollected,
                $collected,
                __('finance.description.cod_owed', ['order' => $delivery->order->number]),
            ),
        ], $delivery->order, $delivery, $delivery->delivered_at);
    }
}
