<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * A WGS-84 coordinate pair. Distance maths lives in the DistanceCalculator
 * contract so the straight-line implementation can be swapped for a routing
 * service without touching callers.
 */
final readonly class GeoPoint implements JsonSerializable
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException("Latitude {$latitude} is out of range.");
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException("Longitude {$longitude} is out of range.");
        }
    }

    public static function make(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    public static function tryMake(?float $latitude, ?float $longitude): ?self
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return new self($latitude, $longitude);
    }

    /**
     * Great-circle distance in metres. Kept here (rather than in a service)
     * because it is a pure property of two points, with no policy attached.
     */
    public function haversineMetresTo(self $other): int
    {
        $earthRadius = 6371000.0;

        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($other->latitude);
        $deltaLat = deg2rad($other->latitude - $this->latitude);
        $deltaLng = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function equals(self $other): bool
    {
        return abs($this->latitude - $other->latitude) < 0.0000001
            && abs($this->longitude - $other->longitude) < 0.0000001;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function jsonSerialize(): array
    {
        return ['lat' => $this->latitude, 'lng' => $this->longitude];
    }
}
