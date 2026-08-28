<?php

namespace App\Enums;

/**
 * The public event vocabulary. These strings are part of the API contract —
 * renaming one is a breaking change.
 */
enum WebhookEvent: string
{
    case OrderCreated = 'order.created';
    case DeliverySearching = 'delivery.searching';
    case DeliveryOffered = 'delivery.offered';
    case DeliveryAccepted = 'delivery.accepted';
    case RiderAssigned = 'rider.assigned';
    case RiderArrivedAtPickup = 'rider.arrived_at_pickup';
    case OrderPickedUp = 'order.picked_up';
    case DeliveryStarted = 'delivery.started';
    case DeliveryNearDestination = 'delivery.near_destination';
    case OrderDelivered = 'order.delivered';
    case OrderFailed = 'order.failed';
    case OrderCancelled = 'order.cancelled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function forDeliveryStatus(DeliveryStatus $status): ?self
    {
        return match ($status) {
            DeliveryStatus::Searching => self::DeliverySearching,
            DeliveryStatus::Offered => self::DeliveryOffered,
            DeliveryStatus::Accepted => self::DeliveryAccepted,
            DeliveryStatus::Assigned => self::RiderAssigned,
            DeliveryStatus::ArrivedAtPickup => self::RiderArrivedAtPickup,
            DeliveryStatus::PickedUp => self::OrderPickedUp,
            DeliveryStatus::InTransit => self::DeliveryStarted,
            DeliveryStatus::ArrivedAtDestination => self::DeliveryNearDestination,
            DeliveryStatus::Delivered => self::OrderDelivered,
            DeliveryStatus::Failed, DeliveryStatus::Expired => self::OrderFailed,
            DeliveryStatus::Cancelled => self::OrderCancelled,
            default => null,
        };
    }
}
