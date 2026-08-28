<?php

namespace App\Notifications;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsappChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;

/**
 * The customer has no account, so this goes out on the routes they do have —
 * SMS and WhatsApp — carrying the tracking link rather than any order detail.
 */
class CustomerDeliveryUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Delivery $delivery,
        public readonly DeliveryStatus $status,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable
            ? [SmsChannel::class, WhatsappChannel::class]
            : ['database'];
    }

    public function toSms(object $notifiable): string
    {
        return match ($this->status) {
            DeliveryStatus::PickedUp => __('notification.sms.customer_picked_up', [
                'business' => $this->delivery->business->displayName(),
                'url' => $this->delivery->trackingUrl(),
            ]),
            DeliveryStatus::ArrivedAtDestination => __('notification.sms.customer_arriving'),
            DeliveryStatus::Delivered => __('notification.sms.customer_delivered', [
                'business' => $this->delivery->business->displayName(),
            ]),
            default => __('notification.sms.customer_update', [
                'status' => $this->status->label(),
                'url' => $this->delivery->trackingUrl(),
            ]),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toWhatsapp(object $notifiable): array
    {
        return [
            'template' => 'delivery_status_update',
            'parameters' => [
                $this->delivery->business->displayName(),
                $this->status->label(),
                $this->delivery->trackingUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'customer_update',
            'status' => $this->status->value,
            'tracking_url' => $this->delivery->trackingUrl(),
        ];
    }
}
