<?php

namespace App\Livewire\Admin\Riders;

use App\Enums\RiderStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RiderList extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $company = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Rider>
     */
    public function riders(): LengthAwarePaginator
    {
        return Rider::query()
            ->with('deliveryCompany')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->company !== '', fn ($query) => $query->where('delivery_company_id', $this->company))
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
            ))
            ->orderByRaw("CASE status WHEN 'online' THEN 0 WHEN 'busy' THEN 1 WHEN 'offline' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(30);
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.riders.rider-list', [
            'riders' => $this->riders(),
            'statuses' => RiderStatus::cases(),
            'companies' => DeliveryCompany::query()->orderBy('name')->get(['id', 'name']),
        ], __('app.nav.riders'));
    }
}
