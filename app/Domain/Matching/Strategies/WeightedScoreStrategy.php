<?php

namespace App\Domain\Matching\Strategies;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\Contracts\MatchingStrategy;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use App\Domain\Matching\MatchingEngine;
use Illuminate\Support\Collection;

/**
 * The default balanced strategy: a weighted sum across every registered
 * scorer, plus a bonus for companies the business has marked as preferred.
 *
 * Weights are configuration, not code, so the network's dispatch behaviour
 * can be retuned by an operator between rounds.
 */
final class WeightedScoreStrategy implements MatchingStrategy
{
    /**
     * @param  array<int, CandidateScorer>  $scorers
     */
    public function __construct(
        private readonly array $scorers,
    ) {}

    public function name(): string
    {
        return 'weighted';
    }

    public function rank(Collection $candidates, MatchingContext $context): Collection
    {
        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $weights = MatchingEngine::weights();
        $preferredBonus = (float) config('platform.matching.preferred_company_bonus', 0.15);

        foreach ($candidates as $candidate) {
            $scores = [];
            $total = 0.0;

            foreach ($this->scorers as $scorer) {
                $weight = (float) ($weights[$scorer->key()] ?? 0.0);

                if ($weight <= 0.0) {
                    continue;
                }

                $value = max(0.0, min(1.0, $scorer->score($candidate, $context, $candidates)));
                $scores[$scorer->key()] = $value;
                $total += $value * $weight;
            }

            if ($candidate->isPreferred) {
                $scores['preferred_bonus'] = $preferredBonus;
                $total += $preferredBonus;
            }

            $candidate->scores = $scores;
            $candidate->score = min(1.0, $total);
        }

        return $candidates
            ->sortByDesc(fn (MatchCandidate $candidate) => $candidate->score)
            ->values();
    }
}
