<?php

namespace App\Listeners;

use App\Domain\Deliveries\Events\DeliveryOffersDispatched;
use App\Models\DeliveryOffer;
use App\Notifications\DeliveryOfferReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyCompaniesOfOffers implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(DeliveryOffersDispatched $event): void
    {
        foreach ($event->offers as $offer) {
            /** @var DeliveryOffer $offer */
            $recipients = $offer->deliveryCompany->users()
                ->wherePivot('is_active', true)
                ->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new DeliveryOfferReceived($offer));
        }
    }
}
