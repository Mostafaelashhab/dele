<?php

namespace App\Livewire\Business\Orders;

use App\Actions\Deliveries\CancelDeliveryAction;
use App\Domain\Deliveries\Actor;
use App\Domain\Tenancy\CurrentTenant;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

class OrderDetail extends Component
{
    use UsesPortalLayout;

    public string $orderId = '';

    public string $cancellationReason = '';

    public bool $cancelling = false;

    public const MAP_ID = 'business-order';

    public function mount(string $number): void
    {
        $this->orderId = Order::query()
            ->forBusiness(app(CurrentTenant::class)->businessOrFail())
            ->where('number', $number)
            ->firstOrFail()
            ->id;
    }

    #[Computed]
    public function order(): Order
    {
        return Order::query()
            ->whereKey($this->orderId)
            ->with(['items', 'currentDelivery.deliveryCompany', 'currentDelivery.rider', 'pickupZone', 'dropoffZone'])
            ->firstOrFail();
    }

    /**
     * Only customer-visible events are shown, so the business sees the same
     * story its customer does rather than the dispatch machinery behind it.
     *
     * @return Collection<int, OrderEvent>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->order()->events()->customerVisible()->chronological()->get();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapConfig(): array
    {
        $delivery = $this->order()->currentDelivery;

        if ($delivery === null) {
            return ['markers' => [], 'route' => []];
        }

        return [
            'markers' => MapPayload::legFor($delivery),
            'route' => MapPayload::routeFor($delivery),
        ];
    }

    public function hasMap(): bool
    {
        return ($this->mapConfig()['markers'] ?? []) !== [];
    }

    public function cancel(): void
    {
        $this->validate(['cancellationReason' => ['required', 'string', 'max:200']]);

        $delivery = $this->order()->currentDelivery;

        if ($delivery === null || ! $delivery->isCancellable()) {
            $this->dispatch('toast', message: __('delivery.errors.not_cancellable'), tone: 'error');

            return;
        }

        try {
            app(CancelDeliveryAction::class)->handle(
                delivery: $delivery,
                reason: $this->cancellationReason,
                actor: Actor::user(auth()->user()),
                cancelledBy: 'business',
            );
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), tone: 'error');

            return;
        }

        $this->cancelling = false;
        $this->cancellationReason = '';

        unset($this->order, $this->timeline);
    }

    public function render(): View
    {
        return $this->portalView(
            'livewire.business.orders.order-detail',
            title: $this->order()->number,
        );
    }
}
