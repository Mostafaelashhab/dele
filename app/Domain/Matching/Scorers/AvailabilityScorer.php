<?php

namespace App\Domain\Matching\Scorers;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Spare capacity, measured against the best-supplied company in this pool.
 *
 * Relative rather than absolute, so the score stays meaningful whether the
 * network has three riders online or three hundred.
 */
final class AvailabilityScorer implements CandidateScorer
{
    public function key(): string
    {
        return 'availability';
    }

    public function score(MatchCandidate $candidate, MatchingContext $context, Collection $pool): float
    {
        $best = (int) $pool->max(fn (MatchCandidate $item) => $item->availableRiders);

        if ($best <= 0) {
            return 0.0;
        }

        return min(1.0, $candidate->availableRiders / $best);
    }
}
