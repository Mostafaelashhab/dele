<?php

namespace App\Domain\Zones;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Models\Zone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Maps a coordinate to the operational zone that contains it.
 *
 * Zones are small in number and change rarely, so the whole active set is
 * cached and matched in memory. That keeps resolution off the hot path of
 * every order creation without needing a spatial index.
 */
class ZoneResolver
{
    private const CACHE_KEY = 'zones.active';

    private const CACHE_TTL_MINUTES = 30;

    /**
     * Resolve the zone containing a point.
     *
     * Zone circles overlap in a dense city centre, so a point can legitimately
     * sit inside several. The one whose centre is closest wins, which makes
     * the answer a property of the geography rather than of the order rows
     * happen to be listed in.
     *
     * When nothing contains the point at all — an address just past a
     * boundary, or a map pin dropped loosely — the nearest zone is used, so
     * the delivery still prices and dispatches instead of failing.
     */
    public function resolve(?GeoPoint $point): ?Zone
    {
        if ($point === null) {
            return null;
        }

        $zones = $this->activeZones();

        $containing = $zones
            ->filter(fn (Zone $zone) => $zone->contains($point))
            ->sortBy(fn (Zone $zone) => $zone->distanceTo($point))
            ->first();

        return $containing ?? $this->nearest($point, $zones);
    }

    public function resolveSnapshot(?LocationSnapshot $snapshot): ?Zone
    {
        if ($snapshot === null) {
            return null;
        }

        if ($snapshot->zoneId !== null) {
            $zone = $this->activeZones()->firstWhere('id', $snapshot->zoneId);

            if ($zone !== null) {
                return $zone;
            }
        }

        return $this->resolve($snapshot->point());
    }

    /**
     * @param  Collection<int, Zone>|null  $zones
     */
    public function nearest(GeoPoint $point, ?Collection $zones = null): ?Zone
    {
        return ($zones ?? $this->activeZones())
            ->sortBy(fn (Zone $zone) => $zone->distanceTo($point))
            ->first();
    }

    /**
     * The full active set, cached and matched in memory.
     *
     * Raw attribute rows are cached, never model instances: cache stores
     * restrict which classes they will unserialize, and a cached model also
     * carries whatever relations happened to be loaded when it was written.
     * Hydrating on the way out gives real Zone models with working casts.
     *
     * @return Collection<int, Zone>
     */
    public function activeZones(): Collection
    {
        $rows = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Zone::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (Zone $zone) => $zone->getAttributes())
                ->all(),
        );

        return Zone::hydrate($rows);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
