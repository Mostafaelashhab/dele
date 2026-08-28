<?php

namespace App\Domain\Matching;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Zone;

/**
 * The request the matching engine is answering: where the parcel is, where it
 * must go, and which constraints the business has attached to it.
 */
final readonly class MatchingContext
{
    /**
     * @param  array<int, string>  $blockedCompanyIds
     * @param  array<int, string>  $preferredCompanyIds
     * @param  array<int, string>  $excludeCompanyIds  companies that already declined this round
     */
    public function __construct(
        public Order $order,
        public Delivery $delivery,
        public Business $business,
        public ?GeoPoint $pickupPoint,
        public ?GeoPoint $dropoffPoint,
        public ?Zone $pickupZone,
        public ?Zone $dropoffZone,
        public DeliveryPriority $priority,
        public PackageSize $packageSize,
        public array $blockedCompanyIds = [],
        public array $preferredCompanyIds = [],
        public array $excludeCompanyIds = [],
        public ?string $forcedCompanyId = null,
    ) {}

    public function isPreferred(string $companyId): bool
    {
        return in_array($companyId, $this->preferredCompanyIds, true);
    }

    public function isBlocked(string $companyId): bool
    {
        return in_array($companyId, $this->blockedCompanyIds, true)
            || in_array($companyId, $this->excludeCompanyIds, true);
    }

    /**
     * Companies already offered this delivery in an earlier round are skipped
     * so a rejection is never re-sent to the same inbox.
     */
    public function withExclusions(array $companyIds): self
    {
        return new self(
            order: $this->order,
            delivery: $this->delivery,
            business: $this->business,
            pickupPoint: $this->pickupPoint,
            dropoffPoint: $this->dropoffPoint,
            pickupZone: $this->pickupZone,
            dropoffZone: $this->dropoffZone,
            priority: $this->priority,
            packageSize: $this->packageSize,
            blockedCompanyIds: $this->blockedCompanyIds,
            preferredCompanyIds: $this->preferredCompanyIds,
            excludeCompanyIds: array_values(array_unique([...$this->excludeCompanyIds, ...$companyIds])),
            forcedCompanyId: $this->forcedCompanyId,
        );
    }
}
