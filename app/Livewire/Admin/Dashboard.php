<?php

namespace App\Livewire\Admin;

use App\Domain\Analytics\PlatformMetrics;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use App\Models\DeliveryOffer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    use UsesPortalLayout;

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function metrics(): array
    {
        return app(PlatformMetrics::class)->overview(today()->startOfDay(), today()->endOfDay());
    }

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function operations(): Collection
    {
        return Delivery::query()
            ->active()
            ->with(['order', 'business', 'deliveryCompany', 'rider'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    /**
     * Offers still awaiting an answer, oldest first — the queue an operator
     * intervenes in when a business is waiting too long.
     *
     * @return Collection<int, DeliveryOffer>
     */
    #[Computed]
    public function openOffers(): Collection
    {
        return DeliveryOffer::query()
            ->open()
            ->with(['delivery.order', 'deliveryCompany'])
            ->orderBy('expires_at')
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.dashboard', title: __('app.nav.dashboard'));
    }
}
