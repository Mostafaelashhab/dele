<?php

namespace App\Domain\Matching\Scorers;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Total door-to-door estimate, scored against the configured acceptable
 * ceiling rather than against the pool, so a uniformly slow pool still
 * produces low ETA scores and the operator can see it.
 */
final class EtaScorer implements CandidateScorer
{
    public function key(): string
    {
        return 'eta';
    }

    public function score(MatchCandidate $candidate, MatchingContext $context, Collection $pool): float
    {
        $ceiling = (int) config('platform.matching.max_eta_minutes', 90);

        if ($ceiling <= 0) {
            return 1.0;
        }

        return max(0.0, 1.0 - ($candidate->estimatedTotalMinutes / $ceiling));
    }
}
