<?php

namespace App\Notifications\Channels;

use App\Domain\Notifications\Contracts\WhatsappGateway;
use Illuminate\Notifications\Notification;

class WhatsappChannel
{
    public function __construct(
        private readonly WhatsappGateway $gateway,
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('whatsapp', $notification)
            ?? $notifiable->routeNotificationFor('sms', $notification);

        if (blank($phone)) {
            return;
        }

        $message = $notification->toWhatsapp($notifiable);

        if (is_array($message)) {
            $this->gateway->sendTemplate($phone, $message['template'], $message['parameters'] ?? []);

            return;
        }

        $this->gateway->sendText($phone, (string) $message);
    }
}
