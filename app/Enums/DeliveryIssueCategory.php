<?php

namespace App\Enums;

use App\Models\Delivery;

/**
 * What a recipient says has gone wrong.
 *
 * A fixed list rather than a free-text box alone: a category can be counted,
 * routed and compared across companies, and it asks nothing of somebody who is
 * annoyed and typing on a phone. The note stays optional beside it.
 */
enum DeliveryIssueCategory: string
{
    case Late = 'late';
    case NoContact = 'no_contact';
    case WrongAddress = 'wrong_address';
    case NotReceived = 'not_received';
    case Damaged = 'damaged';
    case Payment = 'payment';
    case Conduct = 'conduct';
    case Other = 'other';

    public function label(): string
    {
        return __('tracking.issue.category.'.$this->value);
    }

    /**
     * Whether this is worth offering for a delivery in its current state.
     *
     * Offering "it has not arrived yet" against a delivery that closed an hour
     * ago, or "the courier is not answering" before one has been assigned,
     * invites a report that describes nothing and wastes the reading of it.
     */
    public function appliesTo(Delivery $delivery): bool
    {
        $delivered = $delivery->status === DeliveryStatus::Delivered;
        $closed = $delivery->status->isTerminal();

        return match ($this) {
            self::NotReceived => $delivered,
            self::Late => ! $closed,
            self::NoContact => ! $closed && $delivery->rider_id !== null,
            self::Damaged, self::Conduct => $delivered,
            default => true,
        };
    }

    /**
     * Reports that need somebody to look at them now rather than in the
     * morning: the parcel is gone, or money is wrong.
     */
    public function isUrgent(): bool
    {
        return in_array($this, [self::NotReceived, self::Payment, self::Conduct], true);
    }

    /**
     * @return array<int, self>
     */
    public static function availableFor(Delivery $delivery): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $category): bool => $category->appliesTo($delivery),
        ));
    }
}
