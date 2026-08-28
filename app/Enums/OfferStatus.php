<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    public function label(): string
    {
        return __('delivery.offer.'.$this->value);
    }
}
