<?php

namespace App\Domain\Providers;

use App\Models\Delivery;

/**
 * The contract every delivery provider satisfies — the platform's own
 * dispatch network today, an external courier's API tomorrow.
 *
 * This is the interface that makes delivery companies interchangeable
 * infrastructure: nothing above it knows or cares which one is carrying a
 * given parcel.
 */
interface DeliveryProviderInterface
{
    /**
     * Stable key stored on delivery_companies.provider.
     */
    public function key(): string;

    public function quote(DeliveryRequest $request): DeliveryQuote;

    public function requestDelivery(DeliveryRequest $request): Delivery;

    public function cancelDelivery(Delivery $delivery, string $reason): void;

    public function trackDelivery(Delivery $delivery): TrackingData;

    /**
     * Whether this provider can serve the request at all, before any quote is
     * attempted — coverage, working hours, capacity.
     */
    public function supports(DeliveryRequest $request): bool;
}
