<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Locked = 'locked';
    case Paid = 'paid';
    case Voided = 'voided';

    public function isMutable(): bool
    {
        return $this === self::Draft || $this === self::Open;
    }

    public function label(): string
    {
        return __('finance.settlement.'.$this->value);
    }
}
