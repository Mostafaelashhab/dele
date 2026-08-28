<?php

namespace App\Domain\Providers;

use App\Models\Delivery;
use App\Models\DeliveryCompany;
use InvalidArgumentException;

/**
 * Resolves the provider responsible for a delivery.
 *
 * Registration is explicit rather than discovered, so adding an integration
 * is one binding in a service provider and no change to any caller.
 */
class DeliveryProviderManager
{
    /**
     * @var array<string, DeliveryProviderInterface>
     */
    private array $providers = [];

    public function register(DeliveryProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function get(string $key): DeliveryProviderInterface
    {
        return $this->providers[$key]
            ?? throw new InvalidArgumentException("No delivery provider registered for [{$key}].");
    }

    public function for(Delivery $delivery): DeliveryProviderInterface
    {
        return $this->get($delivery->provider ?? 'internal');
    }

    public function forCompany(DeliveryCompany $company): DeliveryProviderInterface
    {
        return $this->get($company->provider ?? 'internal');
    }

    /**
     * @return array<string, DeliveryProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }
}
