<?php

namespace App\Enums;

/**
 * Shared lifecycle for tenant accounts (businesses and delivery companies).
 */
enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';

    public function canOperate(): bool
    {
        return $this === self::Active;
    }

    public function label(): string
    {
        return __('account.status.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Pending => 'amber',
            self::Suspended => 'red',
            self::Closed => 'slate',
        };
    }
}
