<?php

namespace App\Livewire\Admin\Businesses;

use App\Domain\Audit\AuditLogger;
use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Business;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BusinessList extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Business>
     */
    public function businesses(): LengthAwarePaginator
    {
        return Business::query()
            ->withCount('deliveries')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    /**
     * Suspension is the platform's only unilateral lever over a tenant, so it
     * is always audited with the actor who pulled it.
     */
    public function toggleStatus(string $id): void
    {
        $business = Business::query()->findOrFail($id);
        $suspending = $business->status === AccountStatus::Active;

        // Written with forceFill because the suspension stamps are kept out
        // of $fillable: nothing driven by user input should be able to set
        // them, and only this deliberate path should.
        $business->forceFill([
            'status' => $suspending ? AccountStatus::Suspended : AccountStatus::Active,
            'suspended_at' => $suspending ? now() : null,
            'suspension_reason' => $suspending ? 'suspended_by_platform' : null,
        ])->save();

        app(AuditLogger::class)->log(
            action: $suspending ? AuditAction::Suspended : AuditAction::Reinstated,
            entity: $business,
            tenantType: 'business',
            tenantId: $business->id,
        );

        $this->resetPage();
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.businesses.business-list', [
            'businesses' => $this->businesses(),
            'statuses' => AccountStatus::cases(),
        ], __('app.nav.businesses'));
    }
}
