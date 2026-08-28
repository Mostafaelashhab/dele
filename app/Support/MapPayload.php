<?php

namespace App\Support;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Models\Delivery;
use App\Models\Rider;
use App\Models\Zone;
use Illuminate\Support\Collection;

/**
 * Builds the payloads the map component consumes.
 *
 * Centralised so every map in the product speaks the same marker vocabulary,
 * and — more importantly — so the decision about what a map is allowed to
 * reveal is made once. The customer-facing tracking map and the operator's
 * live board are built from the same delivery, and only this class decides
 * what each of them sees.
 */
class MapPayload
{
    /**
     * @return array<string, mixed>|null
     */
    public static function fromSnapshot(
        ?LocationSnapshot $snapshot,
        string $key,
        string $variant,
        string $label,
        ?string $title = null,
    ): ?array {
        $point = $snapshot?->point();

        if ($point === null) {
            return null;
        }

        return [
            'key' => $key,
            'lat' => $point->latitude,
            'lng' => $point->longitude,
            'variant' => $variant,
            'label' => $label,
            'title' => $title ?? $label,
            'size' => 30,
        ];
    }

    /**
     * The two ends of a delivery, for a business or operator view.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function legFor(Delivery $delivery): array
    {
        $order = $delivery->order;

        return collect([
            self::fromSnapshot(
                $order->pickupSnapshot(),
                'pickup',
                'pickup',
                '↑',
                __('delivery.labels.pickup').' — '.$order->pickupSnapshot()->contactName,
            ),
            self::fromSnapshot(
                $order->dropoffSnapshot(),
                'dropoff',
                'dropoff',
                '↓',
                __('delivery.labels.dropoff').' — '.$order->dropoffSnapshot()->contactName,
            ),
        ])->filter()->values()->all();
    }

    /**
     * The straight leg between the two ends, for drawing a connector.
     *
     * @return array<int, array{lat: float, lng: float}>
     */
    public static function routeFor(Delivery $delivery): array
    {
        $order = $delivery->order;

        return collect([$order->pickupSnapshot()->point(), $order->dropoffSnapshot()->point()])
            ->filter()
            ->map(fn (GeoPoint $point) => ['lat' => $point->latitude, 'lng' => $point->longitude])
            ->values()
            ->all();
    }

    /**
     * Active deliveries plotted for the operations board.
     *
     * Each marker carries the order number so an operator can click straight
     * through, and late deliveries are recoloured because "which of these is
     * in trouble" is the question the board exists to answer.
     *
     * @param  Collection<int, Delivery>  $deliveries
     * @return array<int, array<string, mixed>>
     */
    public static function deliveries(Collection $deliveries, ?string $routeName = null): array
    {
        return $deliveries
            ->map(function (Delivery $delivery) use ($routeName): ?array {
                $point = $delivery->order->dropoffSnapshot()->point();

                if ($point === null) {
                    return null;
                }

                $late = $delivery->isLate();

                return [
                    'key' => 'delivery-'.$delivery->id,
                    'lat' => $point->latitude,
                    'lng' => $point->longitude,
                    'variant' => $late ? 'late' : 'dropoff',
                    'label' => '',
                    // A pin spends its lower third on the point, so it needs
                    // more room than the dot this used to be.
                    'size' => 30,
                    'title' => $delivery->order->number.' — '.$delivery->status->label(),
                    'popup' => self::deliveryPopup($delivery),
                    'url' => $routeName ? route($routeName, $delivery->order->number) : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Riders currently reporting a position.
     *
     * @param  Collection<int, Rider>  $riders
     * @return array<int, array<string, mixed>>
     */
    public static function riders(Collection $riders): array
    {
        return $riders
            ->map(function (Rider $rider): ?array {
                $point = $rider->currentLocation();

                if ($point === null) {
                    return null;
                }

                return [
                    'key' => 'rider-'.$rider->id,
                    'lat' => $point->latitude,
                    'lng' => $point->longitude,
                    'variant' => 'rider',
                    'label' => mb_substr($rider->name, 0, 1),
                    'size' => 28,
                    'pulse' => $rider->active_deliveries_count > 0,
                    'title' => $rider->name,
                    'popup' => e($rider->name).'<br><span style="color:#64748b">'
                        .e($rider->deliveryCompany?->displayName() ?? '').' · '
                        .e($rider->vehicle_type->label()).'</span>',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Operational zones as circles.
     *
     * @param  Collection<int, Zone>  $zones
     * @return array<int, array<string, mixed>>
     */
    public static function zones(Collection $zones): array
    {
        return $zones
            ->map(fn (Zone $zone) => [
                'lat' => $zone->centroid_latitude,
                'lng' => $zone->centroid_longitude,
                'radius' => $zone->radius_meters,
                'active' => $zone->is_active,
                'label' => $zone->displayName(),
            ])
            ->values()
            ->all();
    }

    /**
     * Zones as pins for the public coverage section.
     *
     * Carries the id the companion list uses to pair with it, and a tone so
     * the furthest band reads warm the same way its row does.
     *
     * @param  Collection<int, Zone>  $zones
     * @return array<int, array<string, mixed>>
     */
    public static function zonePins(Collection $zones): array
    {
        $dearest = (int) $zones->max(fn (Zone $zone) => $zone->basePrice()->minor);

        return $zones
            ->map(fn (Zone $zone) => [
                'id' => $zone->code,
                'lat' => $zone->centroid_latitude,
                'lng' => $zone->centroid_longitude,
                'radius' => $zone->radius_meters,
                'label' => $zone->displayName(),
                'tone' => $zone->basePrice()->minor === $dearest ? 'far' : 'near',
            ])
            ->values()
            ->all();
    }

    private static function deliveryPopup(Delivery $delivery): string
    {
        return '<strong>'.e($delivery->order->number).'</strong><br>'
            .'<span style="color:#64748b">'
            .e($delivery->business->displayName()).' · '
            .e($delivery->status->label())
            .'</span>';
    }
}
