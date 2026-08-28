<?php

namespace App\Livewire\Admin\Companies;

use App\Domain\Audit\AuditLogger;
use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\RiderStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\DeliveryCompany;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CompanyList extends Component
{
    use UsesPortalLayout;

    /**
     * @return Collection<int, DeliveryCompany>
     */
    #[Computed]
    public function companies(): Collection
    {
        return DeliveryCompany::query()
            ->withCount([
                'riders',
                'riders as online_riders_count' => fn ($query) => $query->where('status', RiderStatus::Online),
                'deliveries',
            ])
            ->orderByDesc('status')
            ->orderBy('name')
            ->get();
    }

    public function toggleStatus(string $id): void
    {
        $company = DeliveryCompany::query()->findOrFail($id);
        $suspending = $company->status === AccountStatus::Active;

        // See BusinessList: these stamps are intentionally not fillable.
        $company->forceFill([
            'status' => $suspending ? AccountStatus::Suspended : AccountStatus::Active,
            'suspended_at' => $suspending ? now() : null,
            'suspension_reason' => $suspending ? 'suspended_by_platform' : null,
            'onboarded_at' => $company->onboarded_at ?? now(),
        ])->save();

        app(AuditLogger::class)->log(
            action: $suspending ? AuditAction::Suspended : AuditAction::Reinstated,
            entity: $company,
            description: __('audit.description.company_suspended', ['company' => $company->name]),
            tenantType: 'delivery_company',
            tenantId: $company->id,
        );

        unset($this->companies);
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.companies.company-list', title: __('app.nav.companies'));
    }
}
