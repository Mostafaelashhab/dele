<?php

namespace App\Actions\Deliveries;

use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Enums\DeliveryIssueStatus;
use App\Enums\OrderEventType;
use App\Models\DeliveryIssue;
use App\Models\User;
use RuntimeException;

/**
 * What an operator does with a report once they have read it.
 *
 * Acknowledging is deliberately separate from closing. A dispatcher who has
 * seen a complaint and picked up the phone has done something real, and the
 * recipient watching the tracking page should be able to tell that apart from
 * silence — but it is not an outcome, so it cannot close the report.
 */
class ResolveDeliveryIssueAction
{
    public function __construct(
        private readonly DeliveryTransitioner $transitioner,
    ) {}

    public function acknowledge(DeliveryIssue $issue, User $user): DeliveryIssue
    {
        if ($issue->status !== DeliveryIssueStatus::Open) {
            return $issue;
        }

        $issue->forceFill([
            'status' => DeliveryIssueStatus::Acknowledged,
            'acknowledged_at' => now(),
        ])->save();

        return $issue;
    }

    /**
     * Closing requires saying what was done.
     *
     * A report closed with an empty box is indistinguishable from one that was
     * ignored, and it is the operator's own record as much as anyone's.
     */
    public function resolve(DeliveryIssue $issue, User $user, string $resolution): DeliveryIssue
    {
        $resolution = trim($resolution);

        if ($resolution === '') {
            throw new RuntimeException('A resolution note is required.');
        }

        if ($issue->isResolved()) {
            return $issue;
        }

        $issue->forceFill([
            'status' => DeliveryIssueStatus::Resolved,
            'resolved_at' => now(),
            'resolved_by_user_id' => $user->id,
            'resolution_note' => mb_substr($resolution, 0, 1000),
        ])->save();

        $issue->loadMissing('delivery');

        $this->transitioner->recordEvent(
            $issue->delivery,
            $issue->delivery->status,
            $issue->delivery->status,
            OrderEventType::IssueResolved,
            Actor::user($user),
            [
                'issue_id' => $issue->id,
                'category' => $issue->category->value,
                'resolution' => $issue->resolution_note,
            ],
        );

        return $issue;
    }
}
