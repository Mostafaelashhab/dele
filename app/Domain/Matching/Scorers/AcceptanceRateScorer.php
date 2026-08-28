<?php

namespace App\Domain\Matching\Scorers;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * How often the company answers an offer at all.
 *
 * Offering to a company that habitually lets offers expire costs the business
 * a full offer timeout, so responsiveness is scored directly.
 */
final class AcceptanceRateScorer implements CandidateScorer
{
    private const NEUTRAL_SCORE = 0.6;

    public function key(): string
    {
        return 'acceptance_rate';
    }

    public function score(MatchCandidate $candidate, MatchingContext $context, Collection $pool): float
    {
        $company = $candidate->company;

        if ($company->completed_deliveries_count < 5) {
            return self::NEUTRAL_SCORE;
        }

        return max(0.0, min(1.0, $company->acceptanceRate()));
    }
}
