<?php

namespace App\Domain\Deliveries\Events;

use App\Domain\Deliveries\Actor;
use App\Models\DeliveryOffer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A company declined or let an offer lapse. Not a status change — the
 * delivery is still searching — but it drives re-dispatch and metrics.
 */
class DeliveryOfferRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DeliveryOffer $offer,
        public Actor $actor,
        public bool $expired = false,
    ) {}
}
