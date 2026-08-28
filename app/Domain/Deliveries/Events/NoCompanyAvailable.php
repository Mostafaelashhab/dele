<?php

namespace App\Domain\Deliveries\Events;

use App\Models\Delivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The matching engine found nobody. Distinct from a failure so operations can
 * see supply gaps — the metric that tells the platform where to onboard next.
 */
class NoCompanyAvailable
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Delivery $delivery,
        public int $round,
        public string $reason = 'no_eligible_company',
    ) {}
}
