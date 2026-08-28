<?php

namespace App\Notifications;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent to the business as its delivery moves. Only milestones that a shop
 * owner would act on are sent by SMS; the rest stay in the dashboard, so the
 * platform does not train people to ignore its messages.
 */
class DeliveryProgressed extends Notification implements ShouldQueue
{
    use Queueable;

    private const SMS_WORTHY = [
        DeliveryStatus::PickedUp,
        DeliveryStatus::Delivered,
        DeliveryStatus::Failed,
        DeliveryStatus::Cancelled,
    ];

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
        return in_array($this->status, self::SMS_WORTHY, true)
            ? ['database', SmsChannel::class]
            : ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'delivery_progressed',
            'delivery_id' => $this->delivery->id,
            'order_number' => $this->delivery->order->number,
            'status' => $this->status->value,
            'rider' => $this->delivery->rider?->name,
            'url' => route('business.orders.show', $this->delivery->order->number),
        ];
    }

    public function toSms(object $notifiable): string
    {
        return __('notification.sms.delivery_progressed', [
            'order' => $this->delivery->order->number,
            'status' => $this->status->label(),
        ]);
    }
}
