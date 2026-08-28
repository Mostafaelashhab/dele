<?php

namespace App\Domain\Matching\Contracts;

use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Turns an unordered pool of eligible companies into a ranked shortlist.
 *
 * Swapping strategies is how "cheapest", "fastest" and "balanced" dispatch
 * behave differently without any change to the engine or the dispatcher.
 */
interface MatchingStrategy
{
    public function name(): string;

    /**
     * @param  Collection<int, MatchCandidate>  $candidates
     * @return Collection<int, MatchCandidate> ranked best-first, scores populated
     */
    public function rank(Collection $candidates, MatchingContext $context): Collection;
}
