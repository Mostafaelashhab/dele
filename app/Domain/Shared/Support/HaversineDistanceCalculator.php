<?php

namespace App\Domain\Shared\Support;

use App\Domain\Shared\Contracts\DistanceCalculator;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\RouteEstimate;
use App\Enums\VehicleType;

/**
 * Straight-line distance inflated by a configurable road factor.
 *
 * Banha's street grid is dense and compact, so a tuned factor tracks real
 * riding distance closely enough for pricing and dispatch, with none of the
 * latency or per-request cost of an external routing API.
 */
final readonly class HaversineDistanceCalculator implements DistanceCalculator
{
    public function estimate(
        GeoPoint $origin,
        GeoPoint $destination,
        ?VehicleType $vehicle = null,
    ): RouteEstimate {
        $straightLine = $origin->haversineMetresTo($destination);
        $roadFactor = (float) config('platform.routing.road_factor', 1.32);
        $roadDistance = (int) round($straightLine * $roadFactor);

        $speedKmh = $vehicle?->averageSpeedKmh()
            ?? (float) config('platform.routing.average_speed_kmh', 22.0);

        $travelMinutes = $speedKmh > 0
            ? (int) ceil(($roadDistance / 1000) / $speedKmh * 60)
            : 0;

        return new RouteEstimate(
            distanceMeters: $roadDistance,
            durationMinutes: max(1, $travelMinutes),
            source: 'haversine',
        );
    }
}
