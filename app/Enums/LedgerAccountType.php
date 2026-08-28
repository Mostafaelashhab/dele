<?php

namespace App\Enums;

enum LedgerAccountType: string
{
    case Platform = 'platform';
    case Business = 'business';
    case DeliveryCompany = 'delivery_company';
    case Rider = 'rider';
    case Customer = 'customer';

    public function label(): string
    {
        return __('finance.account.'.$this->value);
    }
}
