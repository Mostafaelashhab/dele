<?php

namespace App\Enums;

use App\Models\Business;
use App\Models\DeliveryCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * The two tenant kinds that partition the network. Platform staff are not a
 * tenant: they operate above the partition.
 */
enum TenantType: string
{
    case Business = 'business';
    case DeliveryCompany = 'delivery_company';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Business => Business::class,
            self::DeliveryCompany => DeliveryCompany::class,
        };
    }

    public function label(): string
    {
        return __('account.tenant.'.$this->value);
    }
}
