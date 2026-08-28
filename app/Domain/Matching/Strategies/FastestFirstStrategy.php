<?php

namespace App\Domain\Matching\Strategies;

use App\Domain\Matching\Contracts\MatchingStrategy;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Time above all else, ties broken by price. Selected by a business that has
 * set its matching preference to "fastest", and by express orders.
 */
final class FastestFirstStrategy implements MatchingStrategy
{
    public function name(): string
    {
        return 'fastest';
    }

    public function rank(Collection $candidates, MatchingContext $context): Collection
    {
        $fastest = (int) $candidates->min(fn (MatchCandidate $candidate) => $candidate->estimatedTotalMinutes);

        foreach ($candidates as $candidate) {
            $ratio = $candidate->estimatedTotalMinutes > 0
                ? $fastest / $candidate->estimatedTotalMinutes
                : 1.0;

            $candidate->scores = ['eta' => $ratio];
            $candidate->score = $ratio;
        }

        return $candidates
            ->sortBy([
                fn (MatchCandidate $a, MatchCandidate $b) => $a->estimatedTotalMinutes <=> $b->estimatedTotalMinutes,
                fn (MatchCandidate $a, MatchCandidate $b) => $a->priceMinor() <=> $b->priceMinor(),
            ])
            ->values();
    }
}
