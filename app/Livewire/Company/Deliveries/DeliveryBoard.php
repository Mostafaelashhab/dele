<?php

namespace App\Livewire\Company\Deliveries;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryBoard extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $rider = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'rider']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Delivery>
     */
    public function deliveries(): LengthAwarePaginator
    {
        return Delivery::query()
            ->forCompany(app(CurrentTenant::class)->companyOrFail())
            ->with(['order', 'business', 'rider'])
            ->when($this->status === 'active', fn ($query) => $query->active())
            ->when(
                $this->status !== '' && $this->status !== 'active',
                fn ($query) => $query->where('status', $this->status)
            )
            ->when($this->rider !== '', fn ($query) => $query->where('rider_id', $this->rider))
            ->when($this->search !== '', fn ($query) => $query->whereHas(
                'order',
                fn ($orders) => $orders->where('number', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function render(): View
    {
        return $this->portalView('livewire.company.deliveries.delivery-board', [
            'deliveries' => $this->deliveries(),
            'riders' => app(CurrentTenant::class)->companyOrFail()->riders()->orderBy('name')->get(),
            'statuses' => DeliveryStatus::cases(),
        ], __('app.nav.deliveries'));
    }
}
