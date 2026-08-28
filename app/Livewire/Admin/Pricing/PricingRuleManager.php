<?php

namespace App\Livewire\Admin\Pricing;

use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PricingRuleType;
use App\Livewire\Concerns\ManagesPricingRules;
use App\Livewire\Concerns\UsesPortalLayout;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Platform-wide default pricing. A company rule of the same type overrides
 * one of these for that company only.
 */
class PricingRuleManager extends Component
{
    use ManagesPricingRules, UsesPortalLayout;

    protected function pricingCompanyId(): ?string
    {
        return null;
    }

    public function render(): View
    {
        return $this->portalView('livewire.pricing.rule-manager', [
            'types' => PricingRuleType::cases(),
            'priorities' => DeliveryPriority::cases(),
            'sizes' => PackageSize::cases(),
            'scopeLabel' => config('platform.name'),
        ], __('app.nav.pricing'));
    }
}
