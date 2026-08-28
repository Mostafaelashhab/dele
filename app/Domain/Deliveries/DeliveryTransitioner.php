<?php

namespace App\Domain\Deliveries;

use App\Domain\Deliveries\Events\DeliveryStatusChanged;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Enums\OrderStatus;
use App\Models\Delivery;
use App\Models\OrderEvent;
use Illuminate\Support\Facades\DB;

/**
 * The only place deliveries.status is written.
 *
 * Each transition runs inside a transaction that re-reads the delivery under
 * a row lock, so two dispatchers clicking "accept" at the same moment cannot
 * both succeed, and the timeline can never record a move that did not happen.
 */
class DeliveryTransitioner
{
    public function __construct(
        private readonly DeliveryStateMachine $stateMachine,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $attributes  extra columns to write atomically with the status
     *
     * @throws Exceptions\InvalidStateTransition
     */
    public function transition(
        Delivery $delivery,
        DeliveryStatus $to,
        OrderEventType $eventType,
        ?Actor $actor = null,
        array $payload = [],
        array $attributes = [],
    ): Delivery {
        $actor ??= Actor::current();

        [$delivery, $from, $event] = DB::transaction(function () use ($delivery, $to, $eventType, $actor, $payload, $attributes) {
            /** @var Delivery $locked */
            $locked = Delivery::query()
                ->whereKey($delivery->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $from = $locked->status;

            $this->stateMachine->assertCanTransition($from, $to);

            $locked->forceFill(array_merge($attributes, ['status' => $to]));

            // Milestone timestamps are derived from the target state, never
            // passed in, so they cannot drift from the status they describe.
            if ($column = $this->stateMachine->timestampColumn($to)) {
                $locked->{$column} ??= now();
            }

            $locked->save();

            $event = $this->recordEvent($locked, $from, $to, $eventType, $actor, $payload);

            $this->syncOrder($locked, $to);

            return [$locked, $from, $event];
        });

        DeliveryStatusChanged::dispatch($delivery, $from, $to, $eventType, $actor, $event, $payload);

        return $delivery;
    }

    /**
     * Record an event without changing state — used for things that happen to
     * a delivery but do not move it, such as a rejected offer.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordEvent(
        Delivery $delivery,
        ?DeliveryStatus $from,
        ?DeliveryStatus $to,
        OrderEventType $eventType,
        Actor $actor,
        array $payload = [],
    ): OrderEvent {
        return OrderEvent::create([
            'order_id' => $delivery->order_id,
            'delivery_id' => $delivery->id,
            'type' => $eventType,
            'from_status' => $from,
            'to_status' => $to,
            'payload' => $payload === [] ? null : $payload,
            'is_customer_visible' => $eventType->isCustomerVisible(),
            'occurred_at' => now(),
            ...$actor->toArray(),
        ]);
    }

    /**
     * Keep the coarse, business-facing order status in step with the delivery.
     *
     * A failed attempt only fails the order when no retry is coming; that
     * decision belongs to the dispatcher, which sets delivery_attempts.
     */
    protected function syncOrder(Delivery $delivery, DeliveryStatus $status): void
    {
        $order = $delivery->order()->lockForUpdate()->first();

        if ($order === null) {
            return;
        }

        $orderStatus = OrderStatus::fromDeliveryStatus($status);

        $attributes = ['status' => $orderStatus];

        if ($orderStatus === OrderStatus::Completed) {
            $attributes['completed_at'] = $order->completed_at ?? now();
        }

        if ($orderStatus === OrderStatus::Cancelled) {
            $attributes['cancelled_at'] = $order->cancelled_at ?? now();
        }

        if ($status === DeliveryStatus::Searching && $order->placed_at === null) {
            $attributes['placed_at'] = now();
        }

        $order->forceFill($attributes)->save();
    }
}
