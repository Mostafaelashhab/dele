<?php

namespace App\Domain\Matching\Scorers;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Track record: how often the company finishes what it accepts, blended with
 * its customer rating.
 *
 * A company with no history yet is given a neutral score so a newly onboarded
 * partner can win work and build a record, instead of being frozen out by a
 * cold start it can never escape.
 */
final class ReliabilityScorer implements CandidateScorer
{
    private const NEUTRAL_SCORE = 0.6;

    public function key(): string
    {
        return 'reliability';
    }

    public function score(MatchCandidate $candidate, MatchingContext $context, Collection $pool): float
    {
        $company = $candidate->company;

        if ($company->completed_deliveries_count < 10) {
            return self::NEUTRAL_SCORE;
        }

        $completion = $company->completionRate();
        $rating = $company->rating_bps > 0 ? $company->rating() / 5 : self::NEUTRAL_SCORE;

        return max(0.0, min(1.0, ($completion * 0.7) + ($rating * 0.3)));
    }
}
