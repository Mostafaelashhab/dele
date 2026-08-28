<?php

namespace App\Actions\Deliveries;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Enums\AssignmentStatus;
use App\Enums\AuditAction;
use App\Enums\DeliveryStatus;
use App\Enums\OfferStatus;
use App\Enums\OrderEventType;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryOffer;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a delivery and unwinds everything it is holding: open offers, the
 * rider's assignment, and the capacity slot that assignment consumed.
 *
 * The state machine already refuses to cancel a parcel that has been picked
 * up — past that point the only honest outcome is delivered or failed.
 */
class CancelDeliveryAction
{
    public function __construct(
        private readonly DeliveryTransitioner $transitioner,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        Delivery $delivery,
        string $reason,
        ?Actor $actor = null,
        string $cancelledBy = 'business',
    ): Delivery {
        $actor ??= Actor::current();

        $this->withdrawOffers($delivery);
        $this->releaseRider($delivery);

        $delivery = $this->transitioner->transition(
            $delivery,
            DeliveryStatus::Cancelled,
            OrderEventType::OrderCancelled,
            $actor,
            ['reason' => $reason, 'cancelled_by' => $cancelledBy],
            ['cancellation_reason' => $reason, 'cancelled_by' => $cancelledBy],
        );

        $delivery->order->forceFill(['cancellation_reason' => $reason])->save();

        $this->auditLogger->log(
            action: AuditAction::StatusChanged,
            entity: $delivery,
            actor: $actor,
            description: __('audit.description.delivery_cancelled', [
                'order' => $delivery->order->number,
                'reason' => $reason,
            ]),
            context: ['cancelled_by' => $cancelledBy],
        );

        return $delivery;
    }

    protected function withdrawOffers(Delivery $delivery): void
    {
        DeliveryOffer::query()
            ->where('delivery_id', $delivery->id)
            ->where('status', OfferStatus::Pending)
            ->update([
                'status' => OfferStatus::Withdrawn->value,
                'responded_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function releaseRider(Delivery $delivery): void
    {
        DB::transaction(function () use ($delivery): void {
            $assignments = DeliveryAssignment::query()
                ->where('delivery_id', $delivery->id)
                ->open()
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                $wasAccepted = $assignment->status === AssignmentStatus::Accepted;

                $assignment->forceFill([
                    'status' => AssignmentStatus::Cancelled,
                    'completed_at' => now(),
                ])->save();

                if (! $wasAccepted) {
                    continue;
                }

                $rider = Rider::query()->whereKey($assignment->rider_id)->lockForUpdate()->first();

                $rider?->forceFill([
                    'active_deliveries_count' => max(0, $rider->active_deliveries_count - 1),
                ])->save();
            }
        });
    }
}
