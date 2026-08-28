<?php

namespace App\Actions\Deliveries;

use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Enums\AssignmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The rider's answer to an assignment.
 *
 * Accepting is what actually moves the delivery to "assigned" and consumes a
 * slot of the rider's capacity; rejecting hands the delivery back to the
 * dispatcher without disturbing its state.
 */
class RespondToAssignmentAction
{
    public function __construct(
        private readonly DeliveryTransitioner $transitioner,
    ) {}

    public function accept(DeliveryAssignment $assignment): Delivery
    {
        $assignment = DB::transaction(function () use ($assignment): DeliveryAssignment {
            /** @var DeliveryAssignment $locked */
            $locked = DeliveryAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isAnswerable()) {
                throw new RuntimeException(__('delivery.errors.assignment_not_answerable'));
            }

            /** @var Rider $rider */
            $rider = Rider::query()->whereKey($locked->rider_id)->lockForUpdate()->firstOrFail();

            if (! $rider->hasCapacity()) {
                throw new RuntimeException(__('delivery.errors.rider_at_capacity'));
            }

            $locked->forceFill([
                'status' => AssignmentStatus::Accepted,
                'accepted_at' => now(),
            ])->save();

            $rider->increment('active_deliveries_count');
            $rider->forceFill(['last_seen_at' => now()])->save();

            return $locked;
        });

        $assignment->loadMissing(['delivery', 'rider']);

        return $this->transitioner->transition(
            $assignment->delivery,
            DeliveryStatus::Assigned,
            OrderEventType::RiderAssigned,
            Actor::rider($assignment->rider),
            [
                'rider_id' => $assignment->rider_id,
                'rider_name' => $assignment->rider->name,
                'vehicle' => $assignment->rider->vehicle_type->value,
            ],
            ['rider_id' => $assignment->rider_id],
        );
    }

    public function reject(
        DeliveryAssignment $assignment,
        ?string $reason = null,
        bool $expired = false,
    ): DeliveryAssignment {
        $assignment = DB::transaction(function () use ($assignment, $reason, $expired): ?DeliveryAssignment {
            /** @var DeliveryAssignment $locked */
            $locked = DeliveryAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== AssignmentStatus::Offered) {
                return null;
            }

            $locked->forceFill([
                'status' => AssignmentStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason ?? ($expired ? 'expired' : null),
            ])->save();

            return $locked;
        }) ?? $assignment->fresh();

        if ($assignment->status !== AssignmentStatus::Rejected) {
            return $assignment;
        }

        $assignment->loadMissing(['delivery', 'rider']);

        $this->transitioner->recordEvent(
            $assignment->delivery,
            $assignment->delivery->status,
            $assignment->delivery->status,
            OrderEventType::RiderAssigned,
            $expired ? Actor::system('assignment_expiry') : Actor::rider($assignment->rider),
            [
                'outcome' => 'rejected',
                'rider_id' => $assignment->rider_id,
                'reason' => $reason,
                'expired' => $expired,
            ],
        );

        return $assignment;
    }
}
