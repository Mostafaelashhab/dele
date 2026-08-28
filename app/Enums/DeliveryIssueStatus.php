<?php

namespace App\Enums;

enum DeliveryIssueStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __('tracking.issue.status.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'red',
            self::Acknowledged => 'amber',
            self::Resolved => 'green',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::Resolved;
    }
}
