<?php

namespace App\Enums;

enum SettlementPeriod: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';

    public function days(): int
    {
        return match ($this) {
            self::Daily => 1,
            self::Weekly => 7,
            self::Biweekly => 14,
            self::Monthly => 30,
        };
    }

    public function label(): string
    {
        return __('finance.period.'.$this->value);
    }
}
