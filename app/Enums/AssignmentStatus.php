<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isOpen(): bool
    {
        return $this === self::Offered || $this === self::Accepted;
    }

    public function label(): string
    {
        return __('delivery.assignment.'.$this->value);
    }
}
