<?php

namespace App\Livewire\Company\Deliveries;

use App\Actions\Deliveries\AssignRiderAction;
use App\Actions\Deliveries\CancelDeliveryAction;
use App\Domain\Deliveries\Actor;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\AssignmentStatus;
use App\Enums\DeliveryStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\OrderEvent;
use App\Models\Rider;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * A company's view of one delivery, and the screen where a dispatcher puts a
 * rider on it.
 *
 * Riders are ranked by how close they already are to the pickup, so the
 * fastest choice is the default one rather than something the dispatcher has
 * to work out from a list of names.
 */
class DeliveryDetail extends Component
{
    use UsesPortalLayout;

    public string $deliveryId = '';

    public string $cancellationReason = '';

    public bool $cancelling = false;

    public const MAP_ID = 'company-delivery';

    public function mount(string $delivery): void
    {
        $this->deliveryId = Delivery::query()
            ->where('public_id', $delivery)
            ->forCompany(app(CurrentTenant::class)->companyOrFail())
            ->firstOrFail()
            ->id;
    }

    #[Computed]
    public function delivery(): Delivery
    {
        return Delivery::query()
            ->whereKey($this->deliveryId)
            ->with(['order.items', 'business', 'rider', 'deliveryCompany'])
            ->firstOrFail();
    }

    /**
     * @return Collection<int, DeliveryAssignment>
     */
    #[Computed]
    public function assignments(): Collection
    {
        return DeliveryAssignment::query()
            ->where('delivery_id', $this->deliveryId)
            ->with('rider')
            ->orderByDesc('offered_at')
            ->get();
    }

    /**
     * Riders who can take this delivery, nearest first.
     *
     * @return Collection<int, array{rider: Rider, distance: ?int}>
     */
    #[Computed]
    public function availableRiders(): Collection
    {
        $delivery = $this->delivery();
        $pickup = $delivery->order->pickupSnapshot()->point();

        return Rider::query()
            ->where('delivery_company_id', $delivery->delivery_company_id)
            ->availableForWork()
            ->get()
            ->filter(fn (Rider $rider) => $rider->vehicle_type->maxPackageSize()
                ->weightRank() >= $delivery->order->package_size->weightRank())
            ->map(fn (Rider $rider) => [
                'rider' => $rider,
                'distance' => $pickup === null
                    ? null
                    : $rider->currentLocation()?->haversineMetresTo($pickup),
            ])
            ->sortBy(fn (array $entry) => $entry['distance'] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Pickup, dropoff, and the rider once they are actually carrying it.
     *
     * A dispatcher fielding "where is my order?" answers it from this in a
     * glance rather than reading two addresses aloud.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapConfig(): array
    {
        $delivery = $this->delivery();
        $markers = MapPayload::legFor($delivery);

        $riderPoint = $delivery->rider?->currentLocation();

        if ($riderPoint !== null && in_array($delivery->status, DeliveryStatus::occupiesRider(), true)) {
            $markers[] = [
                'key' => 'rider',
                'lat' => $riderPoint->latitude,
                'lng' => $riderPoint->longitude,
                'variant' => 'rider',
                'label' => mb_substr($delivery->rider->name, 0, 1),
                'size' => 28,
                'pulse' => true,
                'title' => $delivery->rider->name,
            ];
        }

        return ['markers' => $markers, 'route' => MapPayload::routeFor($delivery)];
    }

    public function hasMap(): bool
    {
        return ($this->mapConfig()['markers'] ?? []) !== [];
    }

    /**
     * @return Collection<int, OrderEvent>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->delivery()->events()->chronological()->get();
    }

    public function assign(string $riderId): void
    {
        $rider = Rider::query()
            ->whereKey($riderId)
            ->where('delivery_company_id', $this->delivery()->delivery_company_id)
            ->firstOrFail();

        try {
            app(AssignRiderAction::class)->handle($this->delivery(), $rider, auth()->user());
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), tone: 'error');

            return;
        }

        unset($this->delivery, $this->assignments, $this->availableRiders, $this->timeline);

        $this->dispatch('toast', message: __('delivery.event.RiderAssigned'), tone: 'neutral');
    }

    public function cancel(): void
    {
        $this->validate(['cancellationReason' => ['required', 'string', 'max:200']]);

        try {
            app(CancelDeliveryAction::class)->handle(
                delivery: $this->delivery(),
                reason: $this->cancellationReason,
                actor: Actor::user(auth()->user()),
                cancelledBy: 'delivery_company',
            );
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), tone: 'error');

            return;
        }

        $this->cancelling = false;
        $this->cancellationReason = '';

        unset($this->delivery, $this->assignments, $this->timeline);
    }

    public function hasOpenAssignment(): bool
    {
        return $this->assignments()
            ->contains(fn (DeliveryAssignment $a) => $a->status === AssignmentStatus::Offered);
    }

    public function render(): View
    {
        return $this->portalView(
            'livewire.company.deliveries.delivery-detail',
            title: $this->delivery()->order->number,
        );
    }
}
