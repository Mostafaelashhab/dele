<?php

namespace App\Livewire\Business;

use App\Domain\Tenancy\CurrentTenant;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function customers(): LengthAwarePaginator
    {
        return Customer::query()
            ->where('business_id', app(CurrentTenant::class)->businessOrFail()->id)
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('last_ordered_at')
            ->orderBy('name')
            ->paginate(25);
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.customer-list', [
            'customers' => $this->customers(),
        ], __('app.nav.customers'));
    }
}
