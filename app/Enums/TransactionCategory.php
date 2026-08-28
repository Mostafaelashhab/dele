<?php

namespace App\Enums;

enum TransactionCategory: string
{
    case DeliveryFee = 'delivery_fee';
    case PlatformFee = 'platform_fee';
    case CompanyPayout = 'company_payout';
    case RiderPayout = 'rider_payout';
    case BusinessCharge = 'business_charge';
    case CodCollected = 'cod_collected';
    case CodRemittance = 'cod_remittance';
    case Commission = 'commission';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return __('finance.category.'.$this->value);
    }
}
