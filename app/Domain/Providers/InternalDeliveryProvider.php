<?php

namespace App\Domain\Providers;

use App\Actions\Deliveries\CancelDeliveryAction;
use App\Domain\Deliveries\Actor;
use App\Domain\Dispatch\DispatchService;
use App\Models\Delivery;

/**
 * The platform's own network of onboarded companies.
 *
 * Every other provider is measured against this one: it is a full
 * implementation of the contract, not a special case the rest of the system
 * knows about.
 */
class InternalDeliveryProvider implements DeliveryProviderInterface
{
    public function __construct(
        private readonly DispatchService $dispatcher,
        private readonly CancelDeliveryAction $cancelDelivery,
    ) {}

    public function key(): string
    {
        return 'internal';
    }

    public function supports(DeliveryRequest $request): bool
    {
        return true;
    }

    /**
     * The indicative price already computed at order time. Binding pricing
     * comes from whichever company accepts.
     */
    public function quote(DeliveryRequest $request): DeliveryQuote
    {
        return new DeliveryQuote(
            providerKey: $this->key(),
            price: $request->delivery->price(),
            estimatedMinutes: $request->delivery->estimated_minutes,
            metadata: ['breakdown' => $request->delivery->price_breakdown],
        );
    }

    public function requestDelivery(DeliveryRequest $request): Delivery
    {
        $this->dispatcher->dispatch($request->delivery);

        return $request->delivery->refresh();
    }

    public function cancelDelivery(Delivery $delivery, string $reason): void
    {
        $this->cancelDelivery->handle($delivery, $reason, Actor::system('provider'), 'platform');
    }

    public function trackDelivery(Delivery $delivery): TrackingData
    {
        $delivery->loadMissing('rider');

        return new TrackingData(
            status: $delivery->status,
            riderPosition: $delivery->rider?->currentLocation() ?? null,
            estimatedArrival: $delivery->estimatedArrival(),
            riderName: $delivery->rider?->name,
        );
    }
}
