<?php

namespace App\Domain\Matching\Scorers;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * How close the company's nearest free rider already is to the pickup.
 *
 * This is the single strongest predictor of a fast pickup, which is why it
 * carries the heaviest default weight.
 */
final class DistanceScorer implements CandidateScorer
{
    public function key(): string
    {
        return 'distance';
    }

    public function score(MatchCandidate $candidate, MatchingContext $context, Collection $pool): float
    {
        $maximum = (int) config('platform.matching.max_pickup_distance_meters', 12000);
        $distance = $candidate->nearestRiderDistanceMeters;

        // No rider position yet (a company that has not started reporting GPS)
        // scores mid-range rather than being excluded outright.
        if ($distance === null) {
            return 0.5;
        }

        if ($distance >= $maximum) {
            return 0.0;
        }

        return 1.0 - ($distance / $maximum);
    }
}
