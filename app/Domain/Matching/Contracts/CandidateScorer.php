<?php

namespace App\Domain\Matching\Contracts;

use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Scores one candidate on one dimension.
 *
 * Implementations receive the whole pool because most useful scores are
 * relative — the cheapest quote in this pool, the fastest ETA in this pool —
 * and must return a value in 0..1 where 1 is best.
 */
interface CandidateScorer
{
    /**
     * Key matching an entry in config('platform.matching.weights').
     */
    public function key(): string;

    /**
     * @param  Collection<int, MatchCandidate>  $pool
     */
    public function score(MatchCandidate $candidate, MatchingContext $context, Collection $pool): float;
}
