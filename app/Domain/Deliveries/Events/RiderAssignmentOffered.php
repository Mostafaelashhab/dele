<?php

namespace App\Domain\Deliveries\Events;

use App\Models\DeliveryAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderAssignmentOffered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DeliveryAssignment $assignment,
    ) {}
}
