<?php

namespace App\Livewire\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\RiderStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use App\Models\Rider;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The operations wall: every delivery in flight and every reachable rider,
 * plotted on a real map of Banha and refreshed continuously.
 *
 * The map is the primary instrument here, not decoration — an operator is
 * asking "where is everything, and which of it is in trouble", and a table of
 * coordinates cannot answer that. The table beside it answers the follow-up.
 */
class LiveOperations extends Component
{
    use UsesPortalLayout;

    public const MAP_ID = 'admin-live-ops';

    #[Url(except: 'all')]
    public string $focus = 'all';

    public bool $showRiders = true;

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function deliveries(): Collection
    {
        return Delivery::query()
            ->active()
            ->with(['order', 'business', 'deliveryCompany', 'rider'])
            ->when($this->focus === 'unassigned', fn ($query) => $query->whereNull('delivery_company_id'))
            ->when($this->focus === 'late', fn ($query) => $query
                ->whereNotNull('estimated_delivery_at')
                ->where('estimated_delivery_at', '<', now()))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return Collection<int, Rider>
     */
    #[Computed]
    public function riders(): Collection
    {
        return Rider::query()
            ->whereIn('status', [RiderStatus::Online->value, RiderStatus::Busy->value])
            ->whereNotNull('current_latitude')
            ->with('deliveryCompany')
            ->orderByDesc('location_updated_at')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        // isLate() reads delivered_at as well as the estimate, so the partial
        // select has to carry every column the predicate touches.
        $active = Delivery::query()->active()->get([
            'id', 'delivery_company_id', 'status', 'estimated_delivery_at', 'delivered_at',
        ]);

        return [
            'active' => $active->count(),
            'unassigned' => $active->whereNull('delivery_company_id')->count(),
            'late' => $active->filter(fn (Delivery $delivery) => $delivery->isLate())->count(),
            'carrying' => $active->whereIn('status', DeliveryStatus::occupiesRider())->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapConfig(): array
    {
        $markers = MapPayload::deliveries($this->deliveries(), 'admin.orders.show');

        if ($this->showRiders) {
            $markers = array_merge($markers, MapPayload::riders($this->riders()));
        }

        return [
            'markers' => $markers,
            // Zone circles are deliberately absent: on a live board they
            // overlap into an unreadable blur and bury the markers, which are
            // the whole point. They belong on the zone editor, where coverage
            // is the subject rather than the background.
            'zones' => [],
            'route' => [],
            'fit' => false,
            'zoom' => 13,
        ];
    }

    /**
     * Called by the poll.
     *
     * The map lives behind wire:ignore so Leaflet keeps its own DOM, so fresh
     * positions are pushed to it as an event rather than through the morph.
     */
    public function refreshBoard(): void
    {
        unset($this->deliveries, $this->riders, $this->counts);

        $this->dispatch('map-refresh', id: self::MAP_ID, config: $this->mapConfig());
    }

    public function setFocus(string $focus): void
    {
        $this->focus = $focus;
        $this->refreshBoard();
    }

    public function toggleRiders(): void
    {
        $this->showRiders = ! $this->showRiders;
        $this->refreshBoard();
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.live-operations', title: __('app.nav.live'));
    }
}
