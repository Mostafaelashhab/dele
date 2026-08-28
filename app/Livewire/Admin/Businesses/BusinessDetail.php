<?php

namespace App\Livewire\Admin\Businesses;

use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerAccountType;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Business;
use App\Models\Delivery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BusinessDetail extends Component
{
    use UsesPortalLayout;

    public string $businessId = '';

    public function mount(string $business): void
    {
        $this->businessId = Business::query()->findOrFail($business)->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::query()
            ->whereKey($this->businessId)
            ->with(['defaultZone', 'users', 'companyPreferences.deliveryCompany'])
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function stats(): array
    {
        $deliveries = Delivery::query()
            ->where('business_id', $this->businessId)
            ->get(['status', 'price_minor', 'platform_fee_minor', 'created_at', 'delivered_at']);

        $delivered = $deliveries->where('status', DeliveryStatus::Delivered);
        $durations = $delivered->map(fn (Delivery $d) => $d->totalMinutes())->filter();

        return [
            'total' => $deliveries->count(),
            'delivered' => $delivered->count(),
            'failed' => $deliveries->whereIn('status', [
                DeliveryStatus::Failed, DeliveryStatus::Expired,
            ])->count(),
            'volume' => Money::ofMinor(
                (int) $delivered->sum(fn (Delivery $d) => $d->price()->minor)
            ),
            'fees' => Money::ofMinor(
                (int) $delivered->sum(fn (Delivery $d) => $d->platformFee()->minor)
            ),
            'average_minutes' => $durations->isEmpty() ? null : (int) round($durations->avg()),
            'balance' => app(LedgerService::class)
                ->unsettledBalance(LedgerAccountType::Business, $this->businessId)
                ->absolute(),
        ];
    }

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function recentDeliveries(): Collection
    {
        return Delivery::query()
            ->where('business_id', $this->businessId)
            ->with(['order', 'deliveryCompany'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function render(): View
    {
        return $this->portalView(
            'livewire.admin.businesses.business-detail',
            title: $this->business()->name,
        );
    }
}
