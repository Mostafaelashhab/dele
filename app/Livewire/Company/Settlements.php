<?php

namespace App\Livewire\Company;

use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\LedgerAccountType;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\FinancialTransaction;
use App\Models\Settlement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * A company's statement: what it has earned, what has been settled, and the
 * ledger entries behind both.
 */
class Settlements extends Component
{
    use UsesPortalLayout;

    /**
     * @return array<string, Money>
     */
    #[Computed]
    public function balances(): array
    {
        $company = app(CurrentTenant::class)->companyOrFail();
        $ledger = app(LedgerService::class);

        return [
            'lifetime' => $ledger->balance(LedgerAccountType::DeliveryCompany, $company->id),
            'unsettled' => $ledger->unsettledBalance(LedgerAccountType::DeliveryCompany, $company->id),
        ];
    }

    /**
     * @return Collection<int, Settlement>
     */
    #[Computed]
    public function settlements(): Collection
    {
        return Settlement::query()
            ->where('party_type', LedgerAccountType::DeliveryCompany)
            ->where('party_id', app(CurrentTenant::class)->companyOrFail()->id)
            ->orderByDesc('period_end')
            ->limit(24)
            ->get();
    }

    /**
     * @return Collection<int, FinancialTransaction>
     */
    #[Computed]
    public function entries(): Collection
    {
        return FinancialTransaction::query()
            ->forAccount(LedgerAccountType::DeliveryCompany, app(CurrentTenant::class)->companyOrFail()->id)
            ->with('delivery.order')
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();
    }

    public function render(): View
    {
        return $this->portalView('livewire.company.settlements', title: __('app.nav.settlements'));
    }
}
