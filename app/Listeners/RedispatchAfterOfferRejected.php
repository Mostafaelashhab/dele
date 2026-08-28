<?php

namespace App\Listeners;

use App\Domain\Deliveries\Events\DeliveryOfferRejected;
use App\Enums\DeliveryStatus;
use App\Jobs\DispatchDeliveryJob;
use App\Models\DeliveryOffer;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Keeps a rejected delivery moving.
 *
 * Only the last outstanding offer triggers a new round: while any company is
 * still holding an offer, the delivery has a live chance and re-dispatching
 * would put the same parcel in two inboxes at once.
 */
class RedispatchAfterOfferRejected implements ShouldQueue
{
    public string $queue = 'dispatch';

    public function handle(DeliveryOfferRejected $event): void
    {
        $delivery = $event->offer->delivery;

        if ($delivery === null || $delivery->status->isTerminal()) {
            return;
        }

        if ($delivery->delivery_company_id !== null) {
            return;
        }

        if (! in_array($delivery->status, [DeliveryStatus::Offered, DeliveryStatus::Searching], true)) {
            return;
        }

        $stillOpen = DeliveryOffer::query()
            ->where('delivery_id', $delivery->id)
            ->open()
            ->exists();

        if ($stillOpen) {
            return;
        }

        DispatchDeliveryJob::dispatch($delivery->id)
            ->delay(now()->addSeconds((int) config('platform.dispatch.requeue_delay_seconds', 5)));
    }
}
