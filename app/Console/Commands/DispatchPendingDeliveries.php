<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Jobs\DispatchDeliveryJob;
use App\Models\Delivery;
use Illuminate\Console\Command;

/**
 * Safety net for deliveries that stalled.
 *
 * Dispatch is event-driven, but a lost queue job on shared hosting would
 * otherwise leave a delivery waiting for ever. This sweeps up anything that
 * has been sitting without an open offer and pushes it back through.
 */
class DispatchPendingDeliveries extends Command
{
    protected $signature = 'banha:dispatch-stalled {--minutes=5 : How long a delivery must have been idle}';

    protected $description = 'Re-dispatch deliveries left without a company or an open offer';

    public function handle(): int
    {
        $threshold = now()->subMinutes((int) $this->option('minutes'));

        $stalled = Delivery::query()
            ->whereIn('status', [
                DeliveryStatus::Pending->value,
                DeliveryStatus::Searching->value,
                DeliveryStatus::Offered->value,
            ])
            ->whereNull('delivery_company_id')
            ->where('updated_at', '<=', $threshold)
            ->whereDoesntHave('offers', fn ($query) => $query->open())
            ->limit(100)
            ->get();

        foreach ($stalled as $delivery) {
            DispatchDeliveryJob::dispatch($delivery->id);
        }

        $this->info("Re-dispatched {$stalled->count()} stalled deliveries.");

        return self::SUCCESS;
    }
}
