<?php

namespace App\Notifications\Channels;

use App\Domain\Notifications\Contracts\SmsGateway;
use Illuminate\Notifications\Notification;

/**
 * Bridges Laravel's notification system to the SMS gateway contract, so a
 * notification declares `toSms()` and knows nothing about the provider.
 */
class SmsChannel
{
    public function __construct(
        private readonly SmsGateway $gateway,
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('sms', $notification);

        if (blank($phone)) {
            return;
        }

        $this->gateway->send($phone, (string) $notification->toSms($notifiable));
    }
}
