<?php

namespace App\Domain\Matching;

use App\Domain\Pricing\PriceQuote;
use App\Models\DeliveryCompany;
use App\Models\Rider;

/**
 * One delivery company evaluated against one delivery request, with the facts
 * the scorers need already gathered so no scorer performs its own queries.
 */
final class MatchCandidate
{
    /**
     * @param  array<string, float>  $scores
     */
    public function __construct(
        public readonly DeliveryCompany $company,
        public readonly PriceQuote $quote,
        public readonly int $availableRiders,
        public readonly ?Rider $nearestRider,
        public readonly ?int $nearestRiderDistanceMeters,
        public readonly int $estimatedPickupMinutes,
        public readonly int $estimatedTotalMinutes,
        public readonly bool $isPreferred = false,
        public array $scores = [],
        public float $score = 0.0,
    ) {}

    public function priceMinor(): int
    {
        return $this->quote->total->minor;
    }

    public function scoreBasisPoints(): int
    {
        return (int) round(max(0.0, min(1.0, $this->score)) * 10000);
    }

    /**
     * The record written to delivery_offers.score_breakdown, so a dispatch
     * decision can be re-examined long after the fact.
     *
     * @return array<string, mixed>
     */
    public function toBreakdown(): array
    {
        return [
            'total_score' => round($this->score, 6),
            'scores' => array_map(fn (float $value) => round($value, 6), $this->scores),
            'weights' => MatchingEngine::weights(),
            'facts' => [
                'available_riders' => $this->availableRiders,
                'nearest_rider_distance_meters' => $this->nearestRiderDistanceMeters,
                'estimated_pickup_minutes' => $this->estimatedPickupMinutes,
                'estimated_total_minutes' => $this->estimatedTotalMinutes,
                'price_minor' => $this->priceMinor(),
                'is_preferred' => $this->isPreferred,
                'acceptance_rate' => $this->company->acceptanceRate(),
                'completion_rate' => $this->company->completionRate(),
            ],
        ];
    }
}
