<?php

namespace App\Actions\Orders;

use App\Domain\Deliveries\Actor;
use App\Domain\Orders\OrderData;
use App\Domain\Orders\OrderNumberGenerator;
use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\Contracts\DistanceCalculator;
use App\Domain\Shared\ValueObjects\RouteEstimate;
use App\Domain\Zones\ZoneResolver;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Enums\OrderStatus;
use App\Jobs\DispatchDeliveryJob;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates an order and its first delivery attempt, prices it, and hands it to
 * the dispatcher.
 *
 * The order, its delivery, its items and its opening events are written in
 * one transaction: a business either has a complete, dispatchable delivery or
 * it has nothing.
 */
class CreateOrderAction
{
    public function __construct(
        private readonly ZoneResolver $zoneResolver,
        private readonly DistanceCalculator $distanceCalculator,
        private readonly PricingEngine $pricingEngine,
        private readonly OrderNumberGenerator $numberGenerator,
    ) {}

    public function handle(
        Business $business,
        OrderData $data,
        ?User $creator = null,
        ?string $apiClientId = null,
        bool $dispatchImmediately = true,
    ): Order {
        $pickupZone = $this->zoneResolver->resolveSnapshot($data->pickup);
        $dropoffZone = $this->zoneResolver->resolveSnapshot($data->dropoff);

        $route = $this->estimateRoute($data);

        $order = DB::transaction(function () use (
            $business, $data, $creator, $apiClientId, $pickupZone, $dropoffZone, $route
        ): Order {
            $order = Order::create([
                'business_id' => $business->id,
                'customer_id' => $data->customerId,
                'created_by_user_id' => $creator?->id,
                'api_client_id' => $apiClientId,
                'reference' => $data->reference,
                'number' => $this->numberGenerator->generate(),
                'status' => OrderStatus::Pending,
                'pickup' => $data->pickup,
                'dropoff' => $data->dropoff,
                'pickup_zone_id' => $pickupZone?->id,
                'dropoff_zone_id' => $dropoffZone?->id,
                'priority' => $data->priority,
                'package_size' => $data->packageSize,
                'package_weight_grams' => $data->packageWeightGrams,
                'payment_type' => $data->paymentType,
                'cod_amount_minor' => $data->cod(),
                'declared_value_minor' => $data->declared(),
                'currency' => config('platform.currency.code'),
                'notes' => $data->notes,
                'scheduled_for' => $data->scheduledFor,
                'metadata' => $data->metadata === [] ? null : $data->metadata,
            ]);

            $order->forceFill(['placed_at' => now()])->save();

            $this->createItems($order, $data);

            $delivery = $this->createDelivery($order, $business, $data, $pickupZone, $dropoffZone, $route);

            OrderEvent::create([
                'order_id' => $order->id,
                'delivery_id' => $delivery->id,
                'type' => OrderEventType::OrderCreated,
                'to_status' => DeliveryStatus::Pending,
                'payload' => [
                    'reference' => $order->reference,
                    'priority' => $order->priority->value,
                    'distance_meters' => $delivery->distance_meters,
                ],
                'is_customer_visible' => true,
                'occurred_at' => now(),
                ...($creator ? Actor::user($creator) : Actor::business($business))->toArray(),
            ]);

            OrderEvent::create([
                'order_id' => $order->id,
                'delivery_id' => $delivery->id,
                'type' => OrderEventType::PriceQuoted,
                'payload' => $delivery->price_breakdown,
                'is_customer_visible' => false,
                'occurred_at' => now(),
                ...Actor::system('pricing')->toArray(),
            ]);

            $this->touchCustomer($order);

            return $order->setRelation('currentDelivery', $delivery);
        });

        if ($dispatchImmediately && $data->scheduledFor === null) {
            DispatchDeliveryJob::dispatch($order->currentDelivery->id);
        }

        return $order->load(['currentDelivery', 'business', 'items']);
    }

    private function createItems(Order $order, OrderData $data): void
    {
        if ($data->items === []) {
            return;
        }

        $order->items()->createMany(array_map(fn (array $item) => [
            'name' => $item['name'],
            'sku' => $item['sku'] ?? null,
            'quantity' => $item['quantity'] ?? 1,
            'unit_price_minor' => $item['unit_price_minor'] ?? 0,
            'weight_grams' => $item['weight_grams'] ?? null,
            'notes' => $item['notes'] ?? null,
        ], $data->items));
    }

    private function createDelivery(
        Order $order,
        Business $business,
        OrderData $data,
        mixed $pickupZone,
        mixed $dropoffZone,
        RouteEstimate $route,
    ): Delivery {
        // An indicative quote with no company attached: what the business is
        // shown before anyone accepts. The binding price is the accepted
        // offer's, which may differ if a company prices its own work.
        $quote = $this->pricingEngine->quote(new PricingContext(
            distanceMeters: $route->distanceMeters,
            estimatedMinutes: $route->durationMinutes,
            priority: $data->priority,
            packageSize: $data->packageSize,
            paymentType: $data->paymentType,
            codAmount: $data->cod(),
            pickupZone: $pickupZone,
            dropoffZone: $dropoffZone,
            business: $business,
        ));

        $totalMinutes = $route->durationMinutes
            + (int) config('platform.routing.pickup_handling_minutes', 6)
            + (int) config('platform.routing.dropoff_handling_minutes', 4);

        return Delivery::create([
            'order_id' => $order->id,
            'business_id' => $business->id,
            'status' => DeliveryStatus::Pending,
            'attempt' => 1,
            'distance_meters' => $route->distanceMeters,
            'estimated_minutes' => $totalMinutes,
            'estimated_delivery_at' => now()->addMinutes($totalMinutes),
            'currency' => $quote->currency(),
            'price_minor' => $quote->total,
            'platform_fee_minor' => $quote->platformFee,
            'company_payout_minor' => $quote->companyPayout,
            'rider_payout_minor' => $quote->riderPayout,
            'price_breakdown' => $quote->jsonSerialize(),
        ]);
    }

    /**
     * Distance between the two ends. When either end has no coordinates we
     * fall back to the zones' estimated time and a nominal distance, so an
     * address typed without a map pin still prices and dispatches.
     */
    private function estimateRoute(OrderData $data): RouteEstimate
    {
        $pickup = $data->pickup->point();
        $dropoff = $data->dropoff->point();

        if ($pickup !== null && $dropoff !== null) {
            return $this->distanceCalculator->estimate($pickup, $dropoff);
        }

        return new RouteEstimate(
            distanceMeters: 3000,
            durationMinutes: 20,
            source: 'zone_fallback',
        );
    }

    private function touchCustomer(Order $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $order->customer()->increment('orders_count', 1, ['last_ordered_at' => now()]);
    }
}
