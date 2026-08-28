<?php

namespace App\Domain\Matching;

use App\Domain\Matching\Contracts\MatchingStrategy;
use App\Domain\Matching\Strategies\CheapestFirstStrategy;
use App\Domain\Matching\Strategies\FastestFirstStrategy;
use App\Domain\Matching\Strategies\PreferredCompanyStrategy;
use App\Domain\Matching\Strategies\WeightedScoreStrategy;
use App\Enums\DeliveryPriority;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the strategy for a specific delivery.
 *
 * Precedence, most specific first: what the business configured, then what
 * the order's priority implies, then the platform default.
 */
class MatchingStrategyFactory
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function for(MatchingContext $context): MatchingStrategy
    {
        $strategy = $this->make($this->resolveName($context));

        return $context->preferredCompanyIds !== [] && ! $strategy instanceof PreferredCompanyStrategy
            ? new PreferredCompanyStrategy($strategy)
            : $strategy;
    }

    public function make(string $name): MatchingStrategy
    {
        return match ($name) {
            'cheapest' => new CheapestFirstStrategy,
            'fastest' => new FastestFirstStrategy,
            default => $this->container->make(WeightedScoreStrategy::class),
        };
    }

    protected function resolveName(MatchingContext $context): string
    {
        if (filled($context->business->matching_strategy)) {
            return $context->business->matching_strategy;
        }

        // An express order is asking for speed by definition; honouring that
        // here means the business does not also have to change its strategy.
        if ($context->priority === DeliveryPriority::Express) {
            return 'fastest';
        }

        return (string) config('platform.matching.strategy', 'weighted');
    }
}
