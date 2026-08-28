<?php

namespace App\Actions\Deliveries;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Domain\Deliveries\Events\RiderAssignmentOffered;
use App\Domain\Shared\Contracts\DistanceCalculator;
use App\Enums\AssignmentStatus;
use App\Enums\AuditAction;
use App\Enums\DeliveryStatus;
use App\Jobs\ExpireRiderAssignmentJob;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A dispatcher offers an accepted delivery to one of their riders.
 *
 * The delivery does not move to "assigned" here — it moves when the rider
 * accepts. Until then the company still owns the problem, which is what stops
 * a parcel sitting unnoticed on an offline rider's phone.
 */
class AssignRiderAction
{
    public function __construct(
        private readonly DistanceCalculator $distanceCalculator,
        private readonly AuditLogger $auditLogger,
        private readonly DeliveryTransitioner $transitioner,
    ) {}

    public function handle(Delivery $delivery, Rider $rider, ?User $assignedBy = null): DeliveryAssignment
    {
        if ($rider->delivery_company_id !== $delivery->delivery_company_id) {
            throw new RuntimeException(__('delivery.errors.rider_wrong_company'));
        }

        if (! $rider->canAcceptWork()) {
            throw new RuntimeException(__('delivery.errors.rider_unavailable'));
        }

        if (! in_array($delivery->status, [DeliveryStatus::Accepted, DeliveryStatus::Assigned], true)) {
            throw new RuntimeException(__('delivery.errors.not_assignable'));
        }

        $assignment = DB::transaction(function () use ($delivery, $rider, $assignedBy): DeliveryAssignment {
            $locked = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            // Any outstanding offer to another rider is cancelled, so a
            // delivery is never live in two riders' queues at once.
            DeliveryAssignment::query()
                ->where('delivery_id', $locked->id)
                ->where('status', AssignmentStatus::Offered)
                ->update([
                    'status' => AssignmentStatus::Cancelled->value,
                    'updated_at' => now(),
                ]);

            $pickupPoint = $locked->order->pickupSnapshot()->point();
            $riderPoint = $rider->currentLocation();

            $pickupDistance = ($pickupPoint && $riderPoint)
                ? $riderPoint->haversineMetresTo($pickupPoint)
                : null;

            $timeout = (int) config('platform.dispatch.rider_offer_timeout_seconds', 60);

            return DeliveryAssignment::create([
                'delivery_id' => $locked->id,
                'rider_id' => $rider->id,
                'delivery_company_id' => $locked->delivery_company_id,
                'status' => AssignmentStatus::Offered,
                'assigned_by_user_id' => $assignedBy?->id,
                'pickup_distance_meters' => $pickupDistance,
                'estimated_pickup_minutes' => $this->pickupMinutes($pickupDistance, $rider),
                'payout_minor' => $locked->riderPayout(),
                'currency' => $locked->currency,
                'offered_at' => now(),
                'expires_at' => now()->addSeconds($timeout),
            ]);
        });

        ExpireRiderAssignmentJob::dispatch($assignment->id)
            ->delay($assignment->expires_at->addSecond());

        $this->auditLogger->log(
            action: AuditAction::RiderAssigned,
            entity: $delivery,
            actor: $assignedBy ? Actor::user($assignedBy) : Actor::system('auto_dispatch'),
            description: __('audit.description.rider_assigned', [
                'rider' => $rider->name,
                'order' => $delivery->order->number,
            ]),
            context: ['assignment_id' => $assignment->id, 'rider_id' => $rider->id],
            tenantType: 'delivery_company',
            tenantId: $delivery->delivery_company_id,
        );

        RiderAssignmentOffered::dispatch($assignment);

        return $assignment;
    }

    private function pickupMinutes(?int $distanceMeters, Rider $rider): ?int
    {
        if ($distanceMeters === null) {
            return null;
        }

        $speed = $rider->vehicle_type->averageSpeedKmh();

        return max(1, (int) ceil(($distanceMeters / 1000) / $speed * 60));
    }
}
