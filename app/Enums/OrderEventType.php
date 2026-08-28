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

    /*
     * A rider was offered the job and did not take it — declined, or let the
     * offer run out.
     *
     * This existed only as a RiderAssigned event carrying an 'outcome' of
     * 'rejected' in its payload, which meant the customer's timeline reported
     * "a rider was assigned" at the moment one refused. On a delivery that
     * went through two riders it appeared twice, and neither entry was true
     * the first time.
     *
     * Kept out of the customer's view entirely: which riders declined before
     * one accepted is the network's business, not the recipient's.
     */
    case RiderDeclined = 'RiderDeclined';
    /*
     * The recipient reported a problem, and the outcome of that report.
     *
     * Internal on purpose. The customer already knows they complained — the
     * tracking page shows them their own report and what came of it — and
     * repeating it inside the delivery's journey would mix "what happened to
     * the parcel" with "what was said about it".
     */
    case IssueReported = 'IssueReported';
    case IssueResolved = 'IssueResolved';
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
