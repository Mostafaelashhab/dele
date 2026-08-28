<?php

namespace App\Providers;

use App\Domain\Matching\Contracts\CandidateScorer;
use App\Domain\Matching\Scorers\AcceptanceRateScorer;
use App\Domain\Matching\Scorers\AvailabilityScorer;
use App\Domain\Matching\Scorers\DistanceScorer;
use App\Domain\Matching\Scorers\EtaScorer;
use App\Domain\Matching\Scorers\PriceScorer;
use App\Domain\Matching\Scorers\ReliabilityScorer;
use App\Domain\Matching\Strategies\WeightedScoreStrategy;
use App\Domain\Notifications\Contracts\PushGateway;
use App\Domain\Notifications\Contracts\SmsGateway;
use App\Domain\Notifications\Contracts\WhatsappGateway;
use App\Domain\Notifications\Gateways\LogPushGateway;
use App\Domain\Notifications\Gateways\LogSmsGateway;
use App\Domain\Notifications\Gateways\LogWhatsappGateway;
use App\Domain\Providers\DeliveryProviderManager;
use App\Domain\Providers\InternalDeliveryProvider;
use App\Domain\Shared\Contracts\DistanceCalculator;
use App\Domain\Shared\Support\HaversineDistanceCalculator;
use App\Domain\Tenancy\ApiContext;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\Business;
use App\Models\Customer;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the domain's boundaries to their implementations.
 *
 * Every binding here is a seam the platform is expected to change at: a real
 * routing service, a real SMS aggregator, an external courier's API.
 */
class PlatformServiceProvider extends ServiceProvider
{
    /**
     * The scorers the weighted matching strategy consults, in the order their
     * contributions are summed.
     *
     * @var array<int, class-string<CandidateScorer>>
     */
    private const SCORERS = [
        DistanceScorer::class,
        AvailabilityScorer::class,
        PriceScorer::class,
        EtaScorer::class,
        ReliabilityScorer::class,
        AcceptanceRateScorer::class,
    ];

    public function register(): void
    {
        $this->bindTenancy();
        $this->bindRouting();
        $this->bindNotificationGateways();
        $this->bindMatching();
        $this->bindDeliveryProviders();
    }

    public function boot(): void
    {
        // Short, stable morph keys: a class name must never become part of
        // the data written to a morph column, or moving a class breaks rows.
        //
        // Enforced, so a model that joins a polymorphic relation without being
        // registered here fails immediately rather than silently writing an
        // FQCN. Every participant must be listed — including User, which
        // Laravel's own notifications table stores against.
        Relation::enforceMorphMap([
            'user' => User::class,
            'business' => Business::class,
            'delivery_company' => DeliveryCompany::class,
            'customer' => Customer::class,
            'rider' => Rider::class,
        ]);
    }

    /**
     * Both hold state scoped to a single request, so they are shared for its
     * duration and rebuilt for the next one.
     */
    private function bindTenancy(): void
    {
        $this->app->scoped(CurrentTenant::class);
        $this->app->scoped(ApiContext::class);
    }

    private function bindRouting(): void
    {
        $this->app->bind(DistanceCalculator::class, function (): DistanceCalculator {
            return match (config('platform.routing.driver')) {
                default => new HaversineDistanceCalculator,
            };
        });
    }

    private function bindNotificationGateways(): void
    {
        $this->app->bind(SmsGateway::class, LogSmsGateway::class);
        $this->app->bind(WhatsappGateway::class, LogWhatsappGateway::class);
        $this->app->bind(PushGateway::class, LogPushGateway::class);
    }

    private function bindMatching(): void
    {
        $this->app->bind(WeightedScoreStrategy::class, function ($app): WeightedScoreStrategy {
            return new WeightedScoreStrategy(
                array_map(fn (string $scorer) => $app->make($scorer), self::SCORERS)
            );
        });
    }

    private function bindDeliveryProviders(): void
    {
        $this->app->singleton(DeliveryProviderManager::class, function ($app): DeliveryProviderManager {
            $manager = new DeliveryProviderManager;
            $manager->register($app->make(InternalDeliveryProvider::class));

            return $manager;
        });
    }
}
