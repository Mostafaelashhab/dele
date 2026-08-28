<?php

namespace App\Enums;

enum PaymentType: string
{
    case Prepaid = 'prepaid';
    case CashOnDelivery = 'cod';

    public function requiresCollection(): bool
    {
        return $this === self::CashOnDelivery;
    }

    public function label(): string
    {
        return __('order.payment.'.$this->value);
    }
}
