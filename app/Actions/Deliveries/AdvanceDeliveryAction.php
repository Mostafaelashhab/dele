<?php

namespace App\Actions\Deliveries;

use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\AssignmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The rider's progress through a delivery: arrived, collected, moving,
 * arrived again, handed over.
 *
 * Each step is a state machine transition, so the sequence cannot be skipped
 * or reordered by a mis-tapped button or a replayed request.
 */
class AdvanceDeliveryAction
{
    public function __construct(
        private readonly DeliveryTransitioner $transitioner,
    ) {}

    public function arrivedAtPickup(Delivery $delivery, Rider $rider): Delivery
    {
        $this->assertRider($delivery, $rider);

        return $this->transitioner->transition(
            $delivery,
            DeliveryStatus::ArrivedAtPickup,
            OrderEventType::RiderArrivedAtPickup,
            Actor::rider($rider),
        );
    }

    public function pickedUp(Delivery $delivery, Rider $rider): Delivery
    {
        $this->assertRider($delivery, $rider);

        return $this->transitioner->transition(
            $delivery,
            DeliveryStatus::PickedUp,
            OrderEventType::OrderPickedUp,
            Actor::rider($rider),
        );
    }

    public function startTransit(Delivery $delivery, Rider $rider): Delivery
    {
        $this->assertRider($delivery, $rider);

        return $this->transitioner->transition(
            $delivery,
            DeliveryStatus::InTransit,
            OrderEventType::DeliveryStarted,
            Actor::rider($rider),
        );
    }

    public function arrivedAtDestination(Delivery $delivery, Rider $rider): Delivery
    {
        $this->assertRider($delivery, $rider);

        return $this->transitioner->transition(
            $delivery,
            DeliveryStatus::ArrivedAtDestination,
            OrderEventType::RiderArrived,
            Actor::rider($rider),
        );
    }

    /**
     * Hand-off. Proof of delivery and any cash collected are written in the
     * same transaction as the status, so a delivered order can never be
     * missing the evidence that it was delivered.
     */
    public function delivered(
        Delivery $delivery,
        Rider $rider,
        ?string $receivedBy = null,
        ?string $proofPhotoPath = null,
        ?Money $codCollected = null,
        ?string $notes = null,
    ): Delivery {
        $this->assertRider($delivery, $rider);

        /*
         * A delivery closes with evidence or it does not close.
         *
         * Either form satisfies it — the recipient's code verified, or a
         * photograph of the parcel — and the check reads the delivery's own
         * state rather than trusting this call's arguments, so a code
         * verified a moment ago on a separate request still counts. The
         * requirement can be turned off for a network that does not want it,
         * but it is on by default: it is the difference between a platform
         * that promises proof and one that hopes for it.
         */
        if (config('platform.proof.require_at_delivery', true)) {
            $verified = $delivery->confirmation_code_verified_at !== null
                || $proofPhotoPath !== null
                || filled($delivery->proof_photo_path);

            if (! $verified) {
                throw new RuntimeException(__('rider.proof.required'));
            }
        }

        $delivery = $this->transitioner->transition(
            $delivery,
            DeliveryStatus::Delivered,
            OrderEventType::OrderDelivered,
            Actor::rider($rider),
            [
                'received_by' => $receivedBy,
                'cod_collected_minor' => $codCollected?->minor,
                'has_proof_photo' => $proofPhotoPath !== null,
                'code_verified' => $delivery->confirmation_code_verified_at !== null,
            ],
            array_filter([
                'received_by' => $receivedBy,
                'proof_photo_path' => $proofPhotoPath,
                'cod_collected_minor' => $codCollected?->minor,
                'delivery_notes' => $notes,
            ], fn ($value) => $value !== null),
        );

        $this->releaseRider($delivery, $rider, AssignmentStatus::Completed);

        return $delivery;
    }

    public function failed(Delivery $delivery, Rider $rider, string $reason, ?string $notes = null): Delivery
    {
        $this->assertRider($delivery, $rider);

        $delivery = $this->transitioner->transition(
            $delivery,
            DeliveryStatus::Failed,
            OrderEventType::OrderFailed,
            Actor::rider($rider),
            ['reason' => $reason],
            array_filter([
                'failure_reason' => $reason,
                'delivery_notes' => $notes,
            ], fn ($value) => $value !== null),
        );

        $this->releaseRider($delivery, $rider, AssignmentStatus::Failed);

        return $delivery;
    }

    /**
     * Free the rider's capacity slot and close their assignment. Decrementing
     * is floored at zero so a double-submitted request cannot drive the
     * counter negative and hand the rider unlimited work.
     */
    protected function releaseRider(Delivery $delivery, Rider $rider, AssignmentStatus $outcome): void
    {
        DB::transaction(function () use ($delivery, $rider, $outcome): void {
            $assignment = DeliveryAssignment::query()
                ->where('delivery_id', $delivery->id)
                ->where('rider_id', $rider->id)
                ->where('status', AssignmentStatus::Accepted)
                ->lockForUpdate()
                ->first();

            if ($assignment === null) {
                return;
            }

            $assignment->forceFill([
                'status' => $outcome,
                'completed_at' => now(),
            ])->save();

            /** @var Rider $locked */
            $locked = Rider::query()->whereKey($rider->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'active_deliveries_count' => max(0, $locked->active_deliveries_count - 1),
                'completed_deliveries_count' => $outcome === AssignmentStatus::Completed
                    ? $locked->completed_deliveries_count + 1
                    : $locked->completed_deliveries_count,
                'last_seen_at' => now(),
            ])->save();
        });
    }

    protected function assertRider(Delivery $delivery, Rider $rider): void
    {
        if ($delivery->rider_id !== $rider->id) {
            throw new RuntimeException(__('delivery.errors.not_your_delivery'));
        }
    }
}
