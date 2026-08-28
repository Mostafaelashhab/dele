<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\Contracts\DistanceCalculator;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Zones\ZoneResolver;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreQuoteRequest;
use App\Http\Resources\Api\V1\QuoteResource;

/**
 * Prices a hypothetical delivery.
 *
 * Lets a shop show a delivery fee at checkout before committing to an order,
 * which is the difference between the platform being usable inside someone
 * else's storefront and only usable in its own dashboard.
 */
class QuoteController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly ZoneResolver $zoneResolver,
        private readonly DistanceCalculator $distanceCalculator,
        private readonly PricingEngine $pricingEngine,
    ) {}

    public function __invoke(StoreQuoteRequest $request): QuoteResource
    {
        $validated = $request->validated();

        $pickup = new GeoPoint((float) $validated['pickup']['lat'], (float) $validated['pickup']['lng']);
        $dropoff = new GeoPoint((float) $validated['dropoff']['lat'], (float) $validated['dropoff']['lng']);

        $route = $this->distanceCalculator->estimate($pickup, $dropoff);
        $codAmount = Money::ofMinor((int) ($validated['cod_amount'] ?? 0));

        $quote = $this->pricingEngine->quote(new PricingContext(
            distanceMeters: $route->distanceMeters,
            estimatedMinutes: $route->durationMinutes
                + (int) config('platform.routing.pickup_handling_minutes')
                + (int) config('platform.routing.dropoff_handling_minutes'),
            priority: DeliveryPriority::from($validated['priority'] ?? DeliveryPriority::Standard->value),
            packageSize: PackageSize::from($validated['package_size'] ?? PackageSize::Small->value),
            paymentType: $codAmount->isPositive() ? PaymentType::CashOnDelivery : PaymentType::Prepaid,
            codAmount: $codAmount,
            pickupZone: $this->zoneResolver->resolve($pickup),
            dropoffZone: $this->zoneResolver->resolve($dropoff),
            business: $this->tenant->business(),
        ));

        return new QuoteResource($quote);
    }
}
