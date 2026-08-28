<?php

namespace App\Domain\Deliveries\Events;

use App\Domain\Deliveries\Actor;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Models\Delivery;
use App\Models\OrderEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised after a delivery has moved between states and the move has been
 * committed. Every side effect — notifications, webhooks, ledger entries,
 * metrics — hangs off this one event rather than off the call sites.
 */
class DeliveryStatusChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Delivery $delivery,
        public DeliveryStatus $from,
        public DeliveryStatus $to,
        public OrderEventType $eventType,
        public Actor $actor,
        public OrderEvent $event,
        public array $payload = [],
    ) {}
}
