<?php

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\RouteEstimate;
use App\Enums\VehicleType;

/**
 * Boundary between the platform and whatever knows about roads. The default
 * binding needs no network; a routing provider can replace it later without
 * any caller changing.
 */
interface DistanceCalculator
{
    public function estimate(
        GeoPoint $origin,
        GeoPoint $destination,
        ?VehicleType $vehicle = null,
    ): RouteEstimate;
}
