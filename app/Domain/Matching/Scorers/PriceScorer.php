<?php

namespace App\Domain\Matching\Scorers;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Cheapest quote in the pool scores 1; the rest fall off proportionally.
 */
final class PriceScorer implements CandidateScorer
{
    public function key(): string
    {
        return 'price';
    }

    public function score(MatchCandidate $candidate, MatchingContext $context, Collection $pool): float
    {
        $cheapest = (int) $pool->min(fn (MatchCandidate $item) => $item->priceMinor());
        $dearest = (int) $pool->max(fn (MatchCandidate $item) => $item->priceMinor());

        // Everyone quoting the same price means price cannot discriminate;
        // returning 1.0 keeps it from dragging every candidate down equally.
        if ($dearest <= $cheapest) {
            return 1.0;
        }

        return 1.0 - (($candidate->priceMinor() - $cheapest) / ($dearest - $cheapest));
    }
}
