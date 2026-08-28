<?php

namespace App\Enums;

/**
 * The canonical delivery lifecycle. Transitions are enforced by the
 * delivery state machine — never assign a status directly.
 */
enum DeliveryStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Searching = 'searching';
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Assigned = 'assigned';
    case ArrivedAtPickup = 'arrived_at_pickup';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case ArrivedAtDestination = 'arrived_at_destination';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * Statuses from which no further transition is possible.
     *
     * @return array<int, self>
     */
    public static function terminal(): array
    {
        return [self::Delivered, self::Failed, self::Cancelled, self::Expired];
    }

    /**
     * Statuses that represent work currently moving through the network.
     *
     * @return array<int, self>
     */
    public static function active(): array
    {
        return [
            self::Pending, self::Searching, self::Offered, self::Accepted,
            self::Assigned, self::ArrivedAtPickup, self::PickedUp,
            self::InTransit, self::ArrivedAtDestination,
        ];
    }

    /**
     * Statuses where a rider is physically responsible for the parcel.
     *
     * @return array<int, self>
     */
    public static function occupiesRider(): array
    {
        return [
            self::Assigned, self::ArrivedAtPickup, self::PickedUp,
            self::InTransit, self::ArrivedAtDestination,
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this, self::terminal(), true);
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    /**
     * Ordinal position used to render the customer facing progress timeline.
     */
    public function timelineStep(): int
    {
        return match ($this) {
            self::Draft, self::Pending => 0,
            self::Searching, self::Offered => 1,
            self::Accepted => 2,
            self::Assigned, self::ArrivedAtPickup => 3,
            self::PickedUp, self::InTransit, self::ArrivedAtDestination => 4,
            self::Delivered => 5,
            self::Failed, self::Cancelled, self::Expired => -1,
        };
    }

    public function label(): string
    {
        return __('delivery.status.'.$this->value);
    }

    /**
     * Tailwind token used by the shared status badge component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Draft, self::Pending => 'neutral',
            self::Searching, self::Offered => 'amber',
            self::Accepted, self::Assigned => 'blue',
            self::ArrivedAtPickup, self::PickedUp, self::InTransit, self::ArrivedAtDestination => 'indigo',
            self::Delivered => 'green',
            self::Failed, self::Expired => 'red',
            self::Cancelled => 'slate',
        };
    }
}
