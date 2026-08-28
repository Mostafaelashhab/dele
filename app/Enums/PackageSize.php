<?php

namespace App\Enums;

enum PackageSize: string
{
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Bulky = 'bulky';

    public function weightRank(): int
    {
        return match ($this) {
            self::Small => 1,
            self::Medium => 2,
            self::Large => 3,
            self::Bulky => 4,
        };
    }

    public function fitsIn(self $capacity): bool
    {
        return $this->weightRank() <= $capacity->weightRank();
    }

    public function label(): string
    {
        return __('delivery.package.'.$this->value);
    }
}
