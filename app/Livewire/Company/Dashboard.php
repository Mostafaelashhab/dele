<?php

namespace App\Livewire\Company;

use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerAccountType;
use App\Enums\RiderStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\DeliveryOffer;
use App\Models\Rider;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    use UsesPortalLayout;

    public const MAP_ID = 'company-live';

    public function company(): DeliveryCompany
    {
        return app(CurrentTenant::class)->companyOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function metrics(): array
    {
        $company = $this->company();

        $riderCounts = Rider::query()
            ->where('delivery_company_id', $company->id)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $completedToday = Delivery::query()
            ->forCompany($company)
            ->where('status', DeliveryStatus::Delivered)
            ->whereDate('delivered_at', today())
            ->get(['company_payout_minor']);

        return [
            'active' => Delivery::query()->forCompany($company)->active()->count(),
            'pending_offers' => DeliveryOffer::query()
                ->where('delivery_company_id', $company->id)
                ->open()
                ->count(),
            'available_riders' => (int) ($riderCounts[RiderStatus::Online->value] ?? 0),
            'busy_riders' => (int) ($riderCounts[RiderStatus::Busy->value] ?? 0),
            'completed_today' => $completedToday->count(),
            'revenue_today' => Money::ofMinor(
                (int) $completedToday->sum(fn (Delivery $d) => $d->companyPayout()->minor)
            ),
            'acceptance_rate' => $company->acceptanceRate(),
            'completion_rate' => $company->completionRate(),
            'unsettled' => app(LedgerService::class)
                ->unsettledBalance(LedgerAccountType::DeliveryCompany, $company->id),
        ];
    }

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function activeDeliveries(): Collection
    {
        return Delivery::query()
            ->forCompany($this->company())
            ->active()
            ->with(['order', 'business', 'rider'])
            ->orderBy('created_at')
            ->limit(15)
            ->get();
    }

    /**
     * @return Collection<int, Rider>
     */
    #[Computed]
    public function riders(): Collection
    {
        return Rider::query()
            ->where('delivery_company_id', $this->company()->id)
            ->whereNot('status', RiderStatus::Suspended)
            ->orderByRaw("CASE status WHEN 'online' THEN 0 WHEN 'busy' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();
    }

    /**
     * The company's own fleet and workload, plotted.
     *
     * Scoped to this company only — the same board the platform runs, but a
     * dispatcher sees their riders and nobody else's.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapConfig(): array
    {
        return [
            'markers' => array_merge(
                MapPayload::deliveries($this->activeDeliveries()),
                MapPayload::riders($this->riders()->filter(
                    fn (Rider $rider) => $rider->currentLocation() !== null
                )),
            ),
            // See LiveOperations: circles bury the markers on a live board.
            'zones' => [],
        ];
    }

    public function refreshBoard(): void
    {
        unset($this->metrics, $this->activeDeliveries, $this->riders, $this->mapConfig);

        $this->dispatch('map-refresh', id: self::MAP_ID, config: $this->mapConfig());
    }

    public function render(): View
    {
        return $this->portalView('livewire.company.dashboard', title: __('app.nav.dashboard'));
    }
}
