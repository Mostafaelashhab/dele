<?php

namespace App\Domain\Deliveries\Events;

use App\Models\Delivery;
use App\Models\DeliveryOffer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DeliveryOffersDispatched
{
    use Dispatchable, SerializesModels;

    /**
     * @param  Collection<int, DeliveryOffer>  $offers
     */
    public function __construct(
        public Delivery $delivery,
        public Collection $offers,
        public int $round,
    ) {}
}
