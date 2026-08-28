<?php

namespace App\Notifications;

use App\Models\DeliveryOffer;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells a delivery company it has work waiting, with the two facts a
 * dispatcher decides on: what it pays and how long they have to answer.
 */
class DeliveryOfferReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DeliveryOffer $offer,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', SmsChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $delivery = $this->offer->delivery;

        return [
            'type' => 'delivery_offer',
            'offer_id' => $this->offer->id,
            'delivery_id' => $delivery->id,
            'order_number' => $delivery->order->number,
            'pickup_area' => $delivery->order->pickupSnapshot()->area,
            'dropoff_area' => $delivery->order->dropoffSnapshot()->area,
            'payout_minor' => $this->offer->payout()->minor,
            'distance_meters' => $delivery->distance_meters,
            'expires_at' => $this->offer->expires_at->toIso8601String(),
            'url' => route('company.offers.show', $this->offer->id),
        ];
    }

    public function toSms(object $notifiable): string
    {
        return __('notification.sms.offer_received', [
            'order' => $this->offer->delivery->order->number,
            'area' => $this->offer->delivery->order->pickupSnapshot()->area ?? config('platform.city'),
            'amount' => $this->offer->payout()->format(),
        ]);
    }
}
