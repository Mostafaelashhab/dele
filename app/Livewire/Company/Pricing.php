<?php

namespace App\Livewire\Company;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PricingRuleType;
use App\Livewire\Concerns\ManagesPricingRules;
use App\Livewire\Concerns\UsesPortalLayout;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Pricing extends Component
{
    use ManagesPricingRules, UsesPortalLayout;

    protected function pricingCompanyId(): ?string
    {
        return app(CurrentTenant::class)->companyOrFail()->id;
    }

    public function render(): View
    {
        return $this->portalView('livewire.pricing.rule-manager', [
            'types' => PricingRuleType::cases(),
            'priorities' => DeliveryPriority::cases(),
            'sizes' => PackageSize::cases(),
            'scopeLabel' => $this->tenantLabel(),
        ], __('app.nav.pricing'));
    }
}
