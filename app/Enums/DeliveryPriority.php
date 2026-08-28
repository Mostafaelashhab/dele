<?php

namespace App\Enums;

enum DeliveryPriority: string
{
    case Standard = 'standard';
    case Express = 'express';
    case Scheduled = 'scheduled';

    /**
     * Pricing multiplier in basis points, resolved from platform config.
     */
    public function multiplierBasisPoints(): int
    {
        return (int) config("platform.pricing.priority_multiplier_bps.{$this->value}", 10000);
    }

    /**
     * How aggressively the dispatcher should widen the candidate pool.
     */
    public function offerFanOut(): int
    {
        return match ($this) {
            self::Express => 3,
            self::Standard => 2,
            self::Scheduled => 1,
        };
    }

    public function label(): string
    {
        return __('delivery.priority.'.$this->value);
    }
}
