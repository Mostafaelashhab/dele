<?php

namespace App\Domain\Matching\Strategies;

use App\Domain\Matching\Contracts\MatchingStrategy;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use Illuminate\Support\Collection;

/**
 * Honours the business's preference list first, then defers to the wrapped
 * strategy for everything else.
 *
 * Deliberately a decorator rather than a copy: a business that prefers one
 * company still wants sane ranking among the rest, and the fallback ordering
 * should improve whenever the underlying strategy does.
 */
final class PreferredCompanyStrategy implements MatchingStrategy
{
    public function __construct(
        private readonly MatchingStrategy $fallback,
    ) {}

    public function name(): string
    {
        return 'preferred';
    }

    public function rank(Collection $candidates, MatchingContext $context): Collection
    {
        $ranked = $this->fallback->rank($candidates, $context);

        return $ranked
            ->sortByDesc(fn (MatchCandidate $candidate) => sprintf(
                '%d-%08.6f',
                $context->isPreferred($candidate->company->id) ? 1 : 0,
                $candidate->score,
            ))
            ->values();
    }
}
