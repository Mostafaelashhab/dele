<?php

namespace App\Enums;

enum ApiClientStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
