<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Database = 'database';
    case Mail = 'mail';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Push = 'push';
    case Broadcast = 'broadcast';

    public function label(): string
    {
        return __('notification.channel.'.$this->value);
    }
}
