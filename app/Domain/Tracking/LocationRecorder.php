<?php

namespace App\Domain\Tracking;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryLocation;
use App\Models\Rider;
use Illuminate\Support\Carbon;

/**
 * Records rider positions, and decides which ones are worth keeping.
 *
 * A rider's phone can report every second. Writing all of it would multiply
 * the largest table in the system by an order of magnitude for no operational
 * gain, so a ping is stored only when enough time has passed or the rider has
 * actually moved.
 */
class LocationRecorder
{
    /**
     * @param  array<string, mixed>  $telemetry
     */
    public function record(
        Rider $rider,
        GeoPoint $point,
        array $telemetry = [],
        ?Delivery $delivery = null,
        ?Carbon $recordedAt = null,
    ): ?DeliveryLocation {
        $recordedAt ??= now();

        $rider->forceFill([
            'current_latitude' => $point->latitude,
            'current_longitude' => $point->longitude,
            'location_updated_at' => $recordedAt,
            'last_seen_at' => $recordedAt,
        ])->save();

        $delivery ??= $this->activeDelivery($rider);

        if (! $this->shouldPersist($rider, $point, $recordedAt)) {
            return null;
        }

        return DeliveryLocation::create([
            'rider_id' => $rider->id,
            'delivery_id' => $delivery?->id,
            'latitude' => $point->latitude,
            'longitude' => $point->longitude,
            'accuracy_meters' => isset($telemetry['accuracy']) ? (int) round($telemetry['accuracy']) : null,
            'heading_degrees' => isset($telemetry['heading']) ? (int) round($telemetry['heading']) % 360 : null,
            'speed_kmh' => isset($telemetry['speed']) ? (int) round($telemetry['speed']) : null,
            'battery_percent' => isset($telemetry['battery']) ? (int) round($telemetry['battery']) : null,
            'status' => $delivery?->status->value ?? $rider->status->value,
            'recorded_at' => $recordedAt,
        ]);
    }

    /**
     * Keep a ping if the rider has moved far enough to matter, or if enough
     * time has passed that a stationary rider still proves they are alive.
     */
    protected function shouldPersist(Rider $rider, GeoPoint $point, Carbon $recordedAt): bool
    {
        $last = DeliveryLocation::query()
            ->where('rider_id', $rider->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        if ($last === null) {
            return true;
        }

        $elapsed = $last->recorded_at->diffInSeconds($recordedAt, absolute: true);
        $interval = (int) config('platform.tracking.ping_interval_seconds', 15);

        if ($elapsed >= $interval) {
            return true;
        }

        if ($elapsed < (int) config('platform.tracking.minimum_ping_interval_seconds', 8)) {
            return false;
        }

        $moved = $last->point()->haversineMetresTo($point);

        return $moved >= (int) config('platform.tracking.minimum_movement_meters', 25);
    }

    protected function activeDelivery(Rider $rider): ?Delivery
    {
        return $rider->deliveries()
            ->whereIn('status', array_column(DeliveryStatus::occupiesRider(), 'value'))
            ->orderByDesc('assigned_at')
            ->first();
    }
}
