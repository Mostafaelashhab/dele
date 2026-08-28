<?php

namespace App\Jobs;

use App\Actions\Deliveries\RespondToAssignmentAction;
use App\Enums\AssignmentStatus;
use App\Models\DeliveryAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Returns a delivery to the dispatcher when the rider never answered.
 */
class ExpireRiderAssignmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $assignmentId,
    ) {
        $this->onQueue('dispatch');
    }

    public function handle(RespondToAssignmentAction $action): void
    {
        $assignment = DeliveryAssignment::query()->with(['delivery', 'rider'])->find($this->assignmentId);

        if ($assignment === null || $assignment->status !== AssignmentStatus::Offered) {
            return;
        }

        // Defer rather than re-dispatch, so an early fire cannot spawn a
        // second job for the same assignment.
        if ($assignment->expires_at?->isFuture()) {
            $this->release($assignment->expires_at->addSecond());

            return;
        }

        $action->reject($assignment, 'timeout', expired: true);
    }
}
