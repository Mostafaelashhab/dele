<?php

namespace App\Domain\Matching\Strategies;

use App\Domain\Matching\Contracts\MatchingStrategy;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Price above all else, ties broken by ETA. Selected by a business that has
 * set its matching preference to "cheapest".
 */
final class CheapestFirstStrategy implements MatchingStrategy
{
    public function name(): string
    {
        return 'cheapest';
    }

    public function rank(Collection $candidates, MatchingContext $context): Collection
    {
        $cheapest = (int) $candidates->min(fn (MatchCandidate $candidate) => $candidate->priceMinor());

        foreach ($candidates as $candidate) {
            $ratio = $candidate->priceMinor() > 0 ? $cheapest / $candidate->priceMinor() : 1.0;

            $candidate->scores = ['price' => $ratio];
            $candidate->score = $ratio;
        }

        return $candidates
            ->sortBy([
                fn (MatchCandidate $a, MatchCandidate $b) => $a->priceMinor() <=> $b->priceMinor(),
                fn (MatchCandidate $a, MatchCandidate $b) => $a->estimatedTotalMinutes <=> $b->estimatedTotalMinutes,
            ])
            ->values();
    }
}
