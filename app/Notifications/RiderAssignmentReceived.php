<?php

namespace App\Notifications;

use App\Models\DeliveryAssignment;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RiderAssignmentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DeliveryAssignment $assignment,
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
        $delivery = $this->assignment->delivery;

        return [
            'type' => 'rider_assignment',
            'assignment_id' => $this->assignment->id,
            'delivery_id' => $delivery->id,
            'order_number' => $delivery->order->number,
            'pickup_area' => $delivery->order->pickupSnapshot()->area,
            'payout_minor' => $this->assignment->payout()->minor,
            'expires_at' => $this->assignment->expires_at?->toIso8601String(),
            'url' => route('rider.deliveries.show', $delivery->public_id),
        ];
    }

    public function toSms(object $notifiable): string
    {
        return __('notification.sms.rider_assignment', [
            'order' => $this->assignment->delivery->order->number,
            'amount' => $this->assignment->payout()->format(),
        ]);
    }
}
