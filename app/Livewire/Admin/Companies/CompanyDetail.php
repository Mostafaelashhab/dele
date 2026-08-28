<?php

namespace App\Livewire\Admin\Companies;

use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerAccountType;
use App\Jobs\RecalculateCompanyMetricsJob;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CompanyDetail extends Component
{
    use UsesPortalLayout;

    public string $companyId = '';

    public function mount(string $company): void
    {
        $this->companyId = DeliveryCompany::query()->findOrFail($company)->id;
    }

    #[Computed]
    public function company(): DeliveryCompany
    {
        return DeliveryCompany::query()
            ->whereKey($this->companyId)
            ->with(['riders', 'serviceAreas', 'users', 'pricingRules'])
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function stats(): array
    {
        $deliveries = Delivery::query()
            ->where('delivery_company_id', $this->companyId)
            ->get(['status', 'company_payout_minor', 'created_at', 'delivered_at', 'accepted_at', 'picked_up_at']);

        $delivered = $deliveries->where('status', DeliveryStatus::Delivered);
        $pickupTimes = $delivered->map(fn (Delivery $d) => $d->pickupMinutes())->filter();

        return [
            'total' => $deliveries->count(),
            'delivered' => $delivered->count(),
            'failed' => $deliveries->whereIn('status', [
                DeliveryStatus::Failed, DeliveryStatus::Expired,
            ])->count(),
            'payout' => Money::ofMinor((int) $delivered->sum(fn (Delivery $d) => $d->companyPayout()->minor)),
            'average_pickup' => $pickupTimes->isEmpty() ? null : (int) round($pickupTimes->avg()),
            'balance' => app(LedgerService::class)
                ->unsettledBalance(LedgerAccountType::DeliveryCompany, $this->companyId),
        ];
    }

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function recentDeliveries(): Collection
    {
        return Delivery::query()
            ->where('delivery_company_id', $this->companyId)
            ->with(['order', 'business', 'rider'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    /**
     * Metrics drive dispatch, so an operator investigating a company needs to
     * be able to force a recalculation rather than wait for the schedule.
     */
    public function refreshMetrics(): void
    {
        RecalculateCompanyMetricsJob::dispatchSync($this->companyId);

        unset($this->company, $this->stats);

        session()->flash('status', __('app.common.refresh'));
    }

    public function render(): View
    {
        return $this->portalView(
            'livewire.admin.companies.company-detail',
            title: $this->company()->name,
        );
    }
}
