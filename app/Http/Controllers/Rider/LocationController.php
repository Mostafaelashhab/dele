<?php

namespace App\Http\Controllers\Rider;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tracking\LocationRecorder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Receives GPS pings from the rider PWA.
 *
 * Deliberately thin and fast: this is the highest-frequency endpoint in the
 * system, and every millisecond here is multiplied by every rider on shift.
 */
class LocationController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentTenant $tenant,
        LocationRecorder $recorder,
    ): JsonResponse {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'heading' => ['nullable', 'numeric', 'min:0', 'max:360'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'battery' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $rider = $tenant->riderOrFail();

        // A phone with a stale clock must not be able to write points into
        // the future or rewrite history, so the timestamp is clamped.
        $recordedAt = isset($validated['recorded_at'])
            ? Carbon::parse($validated['recorded_at'])
            : now();

        if ($recordedAt->isAfter(now()) || $recordedAt->isBefore(now()->subMinutes(10))) {
            $recordedAt = now();
        }

        $stored = $recorder->record(
            rider: $rider,
            point: new GeoPoint((float) $validated['latitude'], (float) $validated['longitude']),
            telemetry: $validated,
            recordedAt: $recordedAt,
        );

        return response()->json([
            'stored' => $stored !== null,
            'next_ping_seconds' => (int) config('platform.tracking.ping_interval_seconds'),
        ]);
    }
}
