<?php

namespace App\Enums;

enum VehicleType: string
{
    case Motorcycle = 'motorcycle';
    case Bicycle = 'bicycle';
    case Car = 'car';
    case Van = 'van';
    case OnFoot = 'on_foot';

    /**
     * Average operating speed in km/h, used by the ETA estimator.
     */
    public function averageSpeedKmh(): float
    {
        return match ($this) {
            self::Motorcycle => 24.0,
            self::Bicycle => 12.0,
            self::Car => 20.0,
            self::Van => 18.0,
            self::OnFoot => 4.5,
        };
    }

    public function maxPackageSize(): PackageSize
    {
        return match ($this) {
            self::OnFoot, self::Bicycle => PackageSize::Small,
            self::Motorcycle => PackageSize::Medium,
            self::Car, self::Van => PackageSize::Bulky,
        };
    }

    public function label(): string
    {
        return __('rider.vehicle.'.$this->value);
    }
}
