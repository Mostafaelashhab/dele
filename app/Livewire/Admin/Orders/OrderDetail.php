<?php

namespace App\Livewire\Admin\Orders;

use App\Actions\Deliveries\CancelDeliveryAction;
use App\Domain\Deliveries\Actor;
use App\Jobs\DispatchDeliveryJob;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * The full operator view of one order: the price it was quoted, every company
 * that was asked, what each answered, and why the engine ranked them so.
 *
 * This is the screen support uses to answer "why did this take so long?",
 * which is why it shows the dispatch record rather than only the outcome.
 */
class OrderDetail extends Component
{
    use UsesPortalLayout;

    public string $orderId = '';

    public string $cancellationReason = '';

    public bool $cancelling = false;

    public const MAP_ID = 'admin-order';

    public function mount(string $number): void
    {
        $this->orderId = Order::query()->where('number', $number)->firstOrFail()->id;
    }

    #[Computed]
    public function order(): Order
    {
        return Order::query()
            ->whereKey($this->orderId)
            ->with(['business', 'items', 'pickupZone', 'dropoffZone', 'createdBy'])
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function attempts(): Collection
    {
        return Delivery::query()
            ->where('order_id', $this->orderId)
            ->with(['deliveryCompany', 'rider', 'offers.deliveryCompany'])
            ->orderBy('attempt')
            ->get();
    }

    /**
     * @return Collection<int, OrderEvent>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->order()->events()->chronological()->get();
    }

    /**
     * @return Collection<int, FinancialTransaction>
     */
    #[Computed]
    public function transactions(): Collection
    {
        return $this->order()->transactions()->orderBy('occurred_at')->get();
    }

    /**
     * The leg, with the rider plotted while they are carrying it.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapConfig(): array
    {
        $delivery = $this->attempts()->last();

        if ($delivery === null) {
            return ['markers' => [], 'route' => []];
        }

        $markers = MapPayload::legFor($delivery);
        $riderPoint = $delivery->rider?->currentLocation();

        if ($riderPoint !== null) {
            $markers[] = [
                'key' => 'rider',
                'lat' => $riderPoint->latitude,
                'lng' => $riderPoint->longitude,
                'variant' => 'rider',
                'label' => mb_substr($delivery->rider->name, 0, 1),
                'size' => 28,
                'pulse' => ! $delivery->status->isTerminal(),
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
     * Manual re-dispatch, for when an operator has fixed whatever blocked the
     * automatic rounds — a zone corrected, a company reinstated.
     */
    public function redispatch(): void
    {
        $delivery = $this->attempts()->last();

        if ($delivery === null || $delivery->delivery_company_id !== null) {
            return;
        }

        DispatchDeliveryJob::dispatch($delivery->id);

        $this->dispatch('toast', message: __('delivery.event.DeliveryRequested'), tone: 'neutral');
    }

    public function cancel(): void
    {
        $this->validate(['cancellationReason' => ['required', 'string', 'max:200']]);

        $delivery = $this->attempts()->last();

        if ($delivery === null || ! $delivery->isCancellable()) {
            $this->dispatch('toast', message: __('delivery.errors.not_cancellable'), tone: 'error');

            return;
        }

        try {
            app(CancelDeliveryAction::class)->handle(
                delivery: $delivery,
                reason: $this->cancellationReason,
                actor: Actor::user(auth()->user()),
                cancelledBy: 'platform',
            );
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), tone: 'error');

            return;
        }

        $this->cancelling = false;
        $this->cancellationReason = '';

        unset($this->order, $this->attempts, $this->timeline);
    }

    public function render(): View
    {
        return $this->portalView(
            'livewire.admin.orders.order-detail',
            title: $this->order()->number,
        );
    }
}
