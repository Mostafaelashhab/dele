<?php

namespace App\Listeners;

use App\Actions\Deliveries\AssignRiderAction;
use App\Domain\Deliveries\Events\DeliveryStatusChanged;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Rider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Picks a rider automatically for companies that have opted into it.
 *
 * Small operators want a dispatcher in the loop; larger ones want the
 * platform to just choose. The flag on the company decides which, and the
 * nearest rider with spare capacity wins.
 */
class AutoAssignRiderOnAcceptance implements ShouldQueue
{
    public string $queue = 'dispatch';

    public function __construct(
        private readonly AssignRiderAction $assignRider,
    ) {}

    public function handle(DeliveryStatusChanged $event): void
    {
        if ($event->to !== DeliveryStatus::Accepted) {
            return;
        }

        $delivery = $event->delivery->loadMissing('deliveryCompany');
        $company = $delivery->deliveryCompany;

        if ($company === null || ! $company->auto_accept_offers) {
            return;
        }

        $rider = $this->nearestAvailableRider($delivery);

        if ($rider === null) {
            Log::info('Auto-assignment found no available rider.', [
                'delivery_id' => $delivery->id,
                'delivery_company_id' => $company->id,
            ]);

            return;
        }

        try {
            $this->assignRider->handle($delivery, $rider);
        } catch (Throwable $exception) {
            // Falling back to manual dispatch is correct here: the company
            // still sees the delivery in its queue and can assign by hand.
            Log::warning('Auto-assignment failed; leaving the delivery for manual dispatch.', [
                'delivery_id' => $delivery->id,
                'rider_id' => $rider->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function nearestAvailableRider(Delivery $delivery): ?Rider
    {
        $pickup = $delivery->order->pickupSnapshot()->point();

        $riders = Rider::query()
            ->where('delivery_company_id', $delivery->delivery_company_id)
            ->availableForWork()
            ->get()
            ->filter(fn (Rider $rider) => $rider->vehicle_type->maxPackageSize()
                ->weightRank() >= $delivery->order->package_size->weightRank());

        if ($riders->isEmpty()) {
            return null;
        }

        if ($pickup === null) {
            return $riders->sortBy('active_deliveries_count')->first();
        }

        return $riders
            ->sortBy(fn (Rider $rider) => $rider->currentLocation()?->haversineMetresTo($pickup) ?? PHP_INT_MAX)
            ->first();
    }
}
