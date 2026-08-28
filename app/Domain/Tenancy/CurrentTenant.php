<?php

namespace App\Domain\Tenancy;

use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use RuntimeException;

/**
 * The tenant this request belongs to.
 *
 * Resolved once by middleware and read everywhere else, so no controller,
 * Livewire component or query has to re-derive it — and none of them can
 * accidentally derive a different one.
 */
class CurrentTenant
{
    private ?Business $business = null;

    private ?DeliveryCompany $company = null;

    private ?Rider $rider = null;

    public function setBusiness(Business $business): void
    {
        $this->business = $business;
    }

    public function setCompany(DeliveryCompany $company): void
    {
        $this->company = $company;
    }

    public function setRider(Rider $rider): void
    {
        $this->rider = $rider;
    }

    public function business(): ?Business
    {
        return $this->business;
    }

    public function company(): ?DeliveryCompany
    {
        return $this->company;
    }

    public function rider(): ?Rider
    {
        return $this->rider;
    }

    /**
     * The tenant, or a hard failure. Used at call sites where operating
     * without a tenant would mean operating across all of them.
     */
    public function businessOrFail(): Business
    {
        return $this->business ?? throw new RuntimeException('No business resolved for this request.');
    }

    public function companyOrFail(): DeliveryCompany
    {
        return $this->company ?? throw new RuntimeException('No delivery company resolved for this request.');
    }

    public function riderOrFail(): Rider
    {
        return $this->rider ?? throw new RuntimeException('No rider resolved for this request.');
    }

    public function hasTenant(): bool
    {
        return $this->business !== null || $this->company !== null;
    }
}
