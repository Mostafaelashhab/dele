<?php

namespace App\Jobs;

use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Domain\Ledger\DeliveryFinancialsRecorder;
use App\Enums\OrderEventType;
use App\Models\Delivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Posts the ledger entries for a completed delivery.
 *
 * Money is written on the queue rather than in the rider's request so a slow
 * or failing posting can retry without the rider's "delivered" tap failing;
 * the recorder itself is idempotent, so retries are safe.
 */
class RecordDeliveryFinancialsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 120, 600];
    }

    public function __construct(
        public readonly string $deliveryId,
    ) {
        $this->onQueue('finance');
    }

    public function handle(DeliveryFinancialsRecorder $recorder, DeliveryTransitioner $transitioner): void
    {
        $delivery = Delivery::query()->with(['order', 'deliveryCompany', 'rider'])->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        if (! $recorder->record($delivery)) {
            return;
        }

        $transitioner->recordEvent(
            $delivery,
            $delivery->status,
            $delivery->status,
            OrderEventType::FinancialsRecorded,
            Actor::system('ledger'),
            [
                'price_minor' => $delivery->price()->minor,
                'platform_fee_minor' => $delivery->platformFee()->minor,
                'company_payout_minor' => $delivery->companyPayout()->minor,
                'rider_payout_minor' => $delivery->riderPayout()->minor,
            ],
        );
    }
}
