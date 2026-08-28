<?php

namespace App\Domain\Matching;

use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\Contracts\DistanceCalculator;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryStatus;
use App\Models\DeliveryCompany;
use App\Models\PlatformSetting;
use App\Models\Rider;
use Illuminate\Support\Collection;

/**
 * Decides which delivery companies should be offered a delivery, and in what
 * order.
 *
 * The engine itself only gathers facts and enforces hard eligibility;
 * everything subjective — what "best" means — lives in the injected strategy
 * and its scorers, so the ranking can evolve without this class changing.
 */
class MatchingEngine
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly DistanceCalculator $distanceCalculator,
        private readonly MatchingStrategyFactory $strategyFactory,
    ) {}

    /**
     * Effective scorer weights: platform config, overridden at runtime by any
     * operator-set value in platform_settings.
     *
     * @return array<string, float>
     */
    public static function weights(): array
    {
        $defaults = (array) config('platform.matching.weights', []);
        $overrides = (array) PlatformSetting::get('matching.weights', []);

        return array_map('floatval', array_merge($defaults, is_array($overrides) ? $overrides : []));
    }

    /**
     * Rank every company that could carry this delivery, best first.
     *
     * @return Collection<int, MatchCandidate>
     */
    public function rank(MatchingContext $context): Collection
    {
        $candidates = $this->buildCandidates($context);

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $strategy = $this->strategyFactory->for($context);
        $minimumScore = (float) config('platform.matching.minimum_score', 0.0);

        return $strategy->rank($candidates, $context)
            ->filter(fn (MatchCandidate $candidate) => $candidate->score >= $minimumScore)
            ->values();
    }

    /**
     * Build a scored-ready candidate for every eligible company.
     *
     * @return Collection<int, MatchCandidate>
     */
    protected function buildCandidates(MatchingContext $context): Collection
    {
        return $this->eligibleCompanies($context)
            ->map(fn (DeliveryCompany $company) => $this->buildCandidate($company, $context))
            ->filter()
            ->values();
    }

    /**
     * Hard eligibility. A company failing any of these is not merely a poor
     * match, it cannot legitimately carry the parcel at all.
     *
     * @return Collection<int, DeliveryCompany>
     */
    protected function eligibleCompanies(MatchingContext $context): Collection
    {
        $query = DeliveryCompany::query()
            ->dispatchable()
            ->with(['serviceAreas', 'riders' => fn ($riders) => $riders->availableForWork()]);

        if ($context->forcedCompanyId !== null) {
            $query->whereKey($context->forcedCompanyId);
        }

        if ($context->blockedCompanyIds !== [] || $context->excludeCompanyIds !== []) {
            $query->whereNotIn('id', array_merge($context->blockedCompanyIds, $context->excludeCompanyIds));
        }

        return $query->get()
            ->filter(fn (DeliveryCompany $company) => $this->coversRoute($company, $context))
            ->filter(fn (DeliveryCompany $company) => $company->isWithinWorkingHours())
            ->filter(fn (DeliveryCompany $company) => $this->hasNetworkCapacity($company))
            ->values();
    }

    /**
     * The company must be willing to collect from the pickup zone and deliver
     * to the dropoff zone. A company with no declared service areas at all is
     * treated as city-wide, which is how a newly onboarded partner starts.
     */
    protected function coversRoute(DeliveryCompany $company, MatchingContext $context): bool
    {
        if ($company->serviceAreas->isEmpty()) {
            return true;
        }

        $covers = function (?string $zoneId, string $column) use ($company): bool {
            if ($zoneId === null) {
                return true;
            }

            $area = $company->serviceAreas->firstWhere('id', $zoneId);

            return $area !== null && (bool) $area->pivot->{$column};
        };

        return $covers($context->pickupZone?->id, 'accepts_pickup')
            && $covers($context->dropoffZone?->id, 'accepts_dropoff');
    }

    /**
     * Guard against handing a company more concurrent work than it declared
     * it can hold — the platform's promise is throughput, not dumping.
     */
    protected function hasNetworkCapacity(DeliveryCompany $company): bool
    {
        $inFlight = $company->deliveries()
            ->whereIn('status', array_column(DeliveryStatus::active(), 'value'))
            ->count();

        return $inFlight < $company->max_concurrent_deliveries;
    }

    protected function buildCandidate(DeliveryCompany $company, MatchingContext $context): ?MatchCandidate
    {
        $riders = $company->riders->filter(
            fn (Rider $rider) => $rider->vehicle_type->maxPackageSize()->weightRank() >= $context->packageSize->weightRank()
        );

        // No rider that can physically carry this parcel means no candidate,
        // regardless of how attractive the company looks on every other axis.
        if ($riders->isEmpty()) {
            return null;
        }

        [$nearestRider, $nearestDistance] = $this->nearestRider($riders, $context->pickupPoint);

        $maxPickupDistance = (int) config('platform.matching.max_pickup_distance_meters', 12000);

        if ($nearestDistance !== null && $nearestDistance > $maxPickupDistance) {
            return null;
        }

        $pickupMinutes = $this->pickupMinutes($nearestRider, $nearestDistance);
        $legMinutes = $this->legMinutes($context, $nearestRider);

        $totalMinutes = $pickupMinutes
            + $legMinutes
            + (int) config('platform.routing.pickup_handling_minutes', 6)
            + (int) config('platform.routing.dropoff_handling_minutes', 4);

        $quote = $this->pricingEngine->quote(new PricingContext(
            distanceMeters: $context->delivery->distance_meters,
            estimatedMinutes: $totalMinutes,
            priority: $context->priority,
            packageSize: $context->packageSize,
            paymentType: $context->order->payment_type,
            codAmount: $context->order->cod_amount_minor ?? Money::zero(),
            pickupZone: $context->pickupZone,
            dropoffZone: $context->dropoffZone,
            business: $context->business,
            deliveryCompany: $company,
        ));

        return new MatchCandidate(
            company: $company,
            quote: $quote,
            availableRiders: $riders->count(),
            nearestRider: $nearestRider,
            nearestRiderDistanceMeters: $nearestDistance,
            estimatedPickupMinutes: $pickupMinutes,
            estimatedTotalMinutes: $totalMinutes,
            isPreferred: $context->isPreferred($company->id),
        );
    }

    /**
     * @param  Collection<int, Rider>  $riders
     * @return array{0: ?Rider, 1: ?int}
     */
    protected function nearestRider(Collection $riders, ?GeoPoint $pickup): array
    {
        if ($pickup === null) {
            return [$riders->first(), null];
        }

        $located = $riders
            ->map(fn (Rider $rider) => [
                'rider' => $rider,
                'distance' => $rider->currentLocation()?->haversineMetresTo($pickup),
            ])
            ->filter(fn (array $entry) => $entry['distance'] !== null)
            ->sortBy('distance');

        if ($located->isEmpty()) {
            return [$riders->first(), null];
        }

        $closest = $located->first();

        return [$closest['rider'], (int) $closest['distance']];
    }

    protected function pickupMinutes(?Rider $rider, ?int $distanceMeters): int
    {
        if ($distanceMeters === null) {
            return $rider?->deliveryCompany?->average_pickup_minutes
                ?? (int) config('platform.routing.pickup_handling_minutes', 6);
        }

        $speed = $rider?->vehicle_type->averageSpeedKmh()
            ?? (float) config('platform.routing.average_speed_kmh', 22.0);

        return max(1, (int) ceil(($distanceMeters / 1000) / $speed * 60));
    }

    protected function legMinutes(MatchingContext $context, ?Rider $rider): int
    {
        if ($context->pickupPoint !== null && $context->dropoffPoint !== null) {
            return $this->distanceCalculator
                ->estimate($context->pickupPoint, $context->dropoffPoint, $rider?->vehicle_type)
                ->durationMinutes;
        }

        return $context->delivery->estimated_minutes
            ?: ($context->dropoffZone?->estimated_minutes ?? 25);
    }
}
