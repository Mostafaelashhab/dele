<?php

namespace App\Notifications;

use App\Models\Delivery;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DeliveryAcceptedByCompany extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Delivery $delivery,
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
        return [
            'type' => 'delivery_accepted',
            'delivery_id' => $this->delivery->id,
            'order_number' => $this->delivery->order->number,
            'company' => $this->delivery->deliveryCompany?->name,
            'price_minor' => $this->delivery->price()->minor,
            'eta_minutes' => $this->delivery->estimated_minutes,
            'url' => route('business.orders.show', $this->delivery->order->number),
        ];
    }

    public function toSms(object $notifiable): string
    {
        return __('notification.sms.delivery_accepted', [
            'order' => $this->delivery->order->number,
            'company' => $this->delivery->deliveryCompany?->name ?? '—',
        ]);
    }
}
