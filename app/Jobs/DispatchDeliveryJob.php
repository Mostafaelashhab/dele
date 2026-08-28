<?php

namespace App\Jobs;

use App\Domain\Dispatch\DispatchService;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/**
 * Runs one dispatch round for a delivery.
 *
 * Keyed overlap protection means a delivery is never dispatched twice
 * concurrently, which would otherwise let a retry and a scheduled re-dispatch
 * both create offers for the same round.
 */
class DispatchDeliveryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly string $deliveryId,
    ) {
        $this->onQueue('dispatch');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->deliveryId))->releaseAfter(15)->expireAfter(120)];
    }

    public function handle(DispatchService $dispatcher): void
    {
        $delivery = Delivery::query()->with('order.business')->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        // A delivery accepted or finished between scheduling and running this
        // job needs no further offers.
        if ($delivery->status->isTerminal() || $delivery->delivery_company_id !== null) {
            return;
        }

        if (! in_array($delivery->status, [
            DeliveryStatus::Pending,
            DeliveryStatus::Searching,
            DeliveryStatus::Offered,
        ], true)) {
            return;
        }

        $offers = $dispatcher->dispatch($delivery);

        Log::info('Dispatch round completed.', [
            'delivery_id' => $delivery->id,
            'offers' => $offers->count(),
            'round' => $delivery->refresh()->dispatch_round,
        ]);
    }

    public function uniqueId(): string
    {
        return $this->deliveryId;
    }
}
