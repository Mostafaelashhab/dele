<?php

namespace App\Enums;

enum RiderStatus: string
{
    case Offline = 'offline';
    case Online = 'online';
    case Busy = 'busy';
    case Suspended = 'suspended';

    public function canReceiveWork(): bool
    {
        return $this === self::Online;
    }

    public function label(): string
    {
        return __('rider.status.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Online => 'green',
            self::Busy => 'amber',
            self::Offline => 'slate',
            self::Suspended => 'red',
        };
    }
}
