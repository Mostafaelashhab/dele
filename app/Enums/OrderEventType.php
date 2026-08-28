<?php

namespace App\Enums;

/**
 * Append-only narrative of everything that happened to an order. Distinct
 * from DeliveryStatus: several event types can share one status.
 */
enum OrderEventType: string
{
    case OrderCreated = 'OrderCreated';
    case DeliveryRequested = 'DeliveryRequested';
    case DeliveryCompanyOffered = 'DeliveryCompanyOffered';
    case DeliveryOfferRejected = 'DeliveryOfferRejected';
    case DeliveryOfferExpired = 'DeliveryOfferExpired';
    case DeliveryAccepted = 'DeliveryAccepted';
    case RiderAssigned = 'RiderAssigned';
    case RiderArrivedAtPickup = 'RiderArrivedAtPickup';
    case OrderPickedUp = 'OrderPickedUp';
    case DeliveryStarted = 'DeliveryStarted';
    case RiderArrived = 'RiderArrived';
    case OrderDelivered = 'OrderDelivered';
    case OrderFailed = 'OrderFailed';
    case OrderCancelled = 'OrderCancelled';
    case DeliveryExpired = 'DeliveryExpired';
    case NoCompanyAvailable = 'NoCompanyAvailable';
    case PriceQuoted = 'PriceQuoted';
    case FinancialsRecorded = 'FinancialsRecorded';

    /**
     * Whether the event should be visible on the public tracking timeline.
     */
    public function isCustomerVisible(): bool
    {
        return in_array($this, [
            self::OrderCreated,
            self::DeliveryAccepted,
            self::RiderAssigned,
            self::RiderArrivedAtPickup,
            self::OrderPickedUp,
            self::DeliveryStarted,
            self::RiderArrived,
            self::OrderDelivered,
            self::OrderFailed,
            self::OrderCancelled,
        ], true);
    }

    public function label(): string
    {
        return __('delivery.event.'.$this->value);
    }
}
