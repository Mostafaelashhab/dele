<?php

namespace App\Livewire\Rider;

use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryStatus;
use App\Enums\RiderStatus;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\Rider;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The rider's home screen.
 *
 * Everything a rider needs in the first two seconds: whether they are on
 * shift, whether there is work, and one large button to act on it.
 */
class Home extends Component
{
    public function rider(): Rider
    {
        return app(CurrentTenant::class)->riderOrFail();
    }

    /**
     * Offers waiting for this rider to accept or decline.
     *
     * @return Collection<int, DeliveryAssignment>
     */
    #[Computed]
    public function pendingAssignments(): Collection
    {
        return DeliveryAssignment::query()
            ->where('rider_id', $this->rider()->id)
            ->awaitingRider()
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with(['delivery.order', 'delivery.business'])
            ->orderBy('offered_at')
            ->get();
    }

    /**
     * Deliveries the rider is currently carrying.
     *
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function activeDeliveries(): Collection
    {
        return Delivery::query()
            ->where('rider_id', $this->rider()->id)
            ->whereIn('status', array_column(DeliveryStatus::occupiesRider(), 'value'))
            ->with(['order', 'business'])
            ->orderBy('assigned_at')
            ->get();
    }

    /**
     * @return array{deliveries: int, earnings: Money}
     */
    #[Computed]
    public function today(): array
    {
        $deliveries = Delivery::query()
            ->where('rider_id', $this->rider()->id)
            ->where('status', DeliveryStatus::Delivered)
            ->whereDate('delivered_at', today())
            ->get(['rider_payout_minor', 'currency']);

        return [
            'deliveries' => $deliveries->count(),
            'earnings' => Money::ofMinor(
                (int) $deliveries->sum(fn (Delivery $delivery) => $delivery->riderPayout()->minor)
            ),
        ];
    }

    /**
     * Going on and off shift.
     *
     * A rider carrying a parcel cannot go offline — the delivery has to be
     * finished or handed back first, because a parcel with an unreachable
     * rider is the worst failure mode this system has.
     */
    public function toggleAvailability(): void
    {
        $rider = $this->rider();

        if ($rider->status === RiderStatus::Suspended) {
            return;
        }

        if ($rider->isOnline() && $this->activeDeliveries()->isNotEmpty()) {
            $this->dispatch('rider-blocked', message: __('rider.app.capacity_full'));

            return;
        }

        $goingOnline = ! $rider->isOnline();

        $rider->forceFill([
            'status' => $goingOnline ? RiderStatus::Online : RiderStatus::Offline,
            'went_online_at' => $goingOnline ? now() : $rider->went_online_at,
            'last_seen_at' => now(),
        ])->save();

        unset($this->pendingAssignments, $this->activeDeliveries);

        $this->dispatch($goingOnline ? 'rider-online' : 'rider-offline');
    }

    public function render(): View
    {
        return view('livewire.rider.home')
            ->layout('components.layouts.rider', ['title' => __('rider.app.name')]);
    }
}
