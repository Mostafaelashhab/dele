<?php

namespace App\Domain\Deliveries;

use App\Domain\Deliveries\Exceptions\InvalidStateTransition;
use App\Enums\DeliveryStatus;

/**
 * The single source of truth for what may follow what.
 *
 * Nothing in the application writes deliveries.status directly; every change
 * goes through here first. That is what makes an impossible delivery — one
 * delivered before it was picked up — unrepresentable rather than merely
 * unlikely.
 */
class DeliveryStateMachine
{
    /**
     * @return array<string, array<int, DeliveryStatus>>
     */
    public static function transitions(): array
    {
        return [
            DeliveryStatus::Draft->value => [
                DeliveryStatus::Pending,
                DeliveryStatus::Cancelled,
            ],
            DeliveryStatus::Pending->value => [
                DeliveryStatus::Searching,
                DeliveryStatus::Cancelled,
                DeliveryStatus::Failed,
            ],
            DeliveryStatus::Searching->value => [
                DeliveryStatus::Offered,
                DeliveryStatus::Accepted,
                DeliveryStatus::Failed,
                DeliveryStatus::Cancelled,
                DeliveryStatus::Expired,
            ],
            // Every company declined, so the dispatcher widens the pool and
            // searches again rather than failing on the first round.
            DeliveryStatus::Offered->value => [
                DeliveryStatus::Accepted,
                DeliveryStatus::Searching,
                DeliveryStatus::Expired,
                DeliveryStatus::Cancelled,
                DeliveryStatus::Failed,
            ],
            // A company that accepted may still hand the work back before a
            // rider is on it; that returns the delivery to the marketplace.
            DeliveryStatus::Accepted->value => [
                DeliveryStatus::Assigned,
                DeliveryStatus::Searching,
                DeliveryStatus::Cancelled,
                DeliveryStatus::Failed,
            ],
            DeliveryStatus::Assigned->value => [
                DeliveryStatus::ArrivedAtPickup,
                DeliveryStatus::Accepted,
                DeliveryStatus::Cancelled,
                DeliveryStatus::Failed,
            ],
            DeliveryStatus::ArrivedAtPickup->value => [
                DeliveryStatus::PickedUp,
                DeliveryStatus::Accepted,
                DeliveryStatus::Cancelled,
                DeliveryStatus::Failed,
            ],
            // Past pickup the parcel is in the rider's hands: cancellation is
            // no longer a business decision, only a failure outcome.
            DeliveryStatus::PickedUp->value => [
                DeliveryStatus::InTransit,
                DeliveryStatus::Failed,
            ],
            DeliveryStatus::InTransit->value => [
                DeliveryStatus::ArrivedAtDestination,
                DeliveryStatus::Delivered,
                DeliveryStatus::Failed,
            ],
            DeliveryStatus::ArrivedAtDestination->value => [
                DeliveryStatus::Delivered,
                DeliveryStatus::Failed,
            ],
            DeliveryStatus::Delivered->value => [],
            DeliveryStatus::Failed->value => [],
            DeliveryStatus::Cancelled->value => [],
            DeliveryStatus::Expired->value => [],
        ];
    }

    /**
     * @return array<int, DeliveryStatus>
     */
    public function allowedFrom(DeliveryStatus $status): array
    {
        return self::transitions()[$status->value] ?? [];
    }

    public function canTransition(DeliveryStatus $from, DeliveryStatus $to): bool
    {
        return in_array($to, $this->allowedFrom($from), true);
    }

    /**
     * @throws InvalidStateTransition
     */
    public function assertCanTransition(DeliveryStatus $from, DeliveryStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw InvalidStateTransition::between($from, $to);
        }
    }

    /**
     * The column stamped when a delivery reaches a status, so milestone
     * timestamps are never set by hand at a call site.
     */
    public function timestampColumn(DeliveryStatus $status): ?string
    {
        return match ($status) {
            DeliveryStatus::Searching => 'searching_at',
            DeliveryStatus::Accepted => 'accepted_at',
            DeliveryStatus::Assigned => 'assigned_at',
            DeliveryStatus::ArrivedAtPickup => 'arrived_at_pickup_at',
            DeliveryStatus::PickedUp => 'picked_up_at',
            DeliveryStatus::InTransit => 'in_transit_at',
            DeliveryStatus::ArrivedAtDestination => 'arrived_at_destination_at',
            DeliveryStatus::Delivered => 'delivered_at',
            DeliveryStatus::Failed, DeliveryStatus::Expired => 'failed_at',
            DeliveryStatus::Cancelled => 'cancelled_at',
            default => null,
        };
    }
}
