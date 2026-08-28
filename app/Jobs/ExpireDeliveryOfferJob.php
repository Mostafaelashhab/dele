<?php

namespace App\Jobs;

use App\Actions\Deliveries\RejectDeliveryOfferAction;
use App\Enums\OfferStatus;
use App\Models\DeliveryOffer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Closes an offer the company never answered.
 *
 * Scheduled at creation time rather than swept periodically, so a business
 * waits exactly the configured timeout and not up to a sweep interval more.
 */
class ExpireDeliveryOfferJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $offerId,
    ) {
        $this->onQueue('dispatch');
    }

    public function handle(RejectDeliveryOfferAction $action): void
    {
        $offer = DeliveryOffer::query()->with(['delivery', 'deliveryCompany'])->find($this->offerId);

        if ($offer === null || $offer->status !== OfferStatus::Pending) {
            return;
        }

        // Guard against an early run: only expire once the deadline has
        // genuinely passed. Releasing defers the job without creating a second
        // one, which re-dispatching here would do on every early fire.
        if ($offer->expires_at->isFuture()) {
            $this->release($offer->expires_at->addSecond());

            return;
        }

        $action->handle($offer, null, 'timeout', expired: true);
    }
}
