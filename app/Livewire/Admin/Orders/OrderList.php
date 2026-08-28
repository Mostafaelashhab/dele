<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\DeliveryStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The platform-wide operations table, with the filters an operator actually
 * reaches for when a business calls to ask where an order is.
 */
class OrderList extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $business = '';

    #[Url(except: '')]
    public string $company = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'business', 'company', 'from', 'to']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Delivery>
     */
    public function deliveries(): LengthAwarePaginator
    {
        return Delivery::query()
            ->with(['order', 'business', 'deliveryCompany', 'rider'])
            ->when($this->status === 'active', fn ($query) => $query->active())
            ->when(
                $this->status !== '' && $this->status !== 'active',
                fn ($query) => $query->where('status', $this->status)
            )
            ->when($this->business !== '', fn ($query) => $query->where('business_id', $this->business))
            ->when($this->company !== '', fn ($query) => $query->where('delivery_company_id', $this->company))
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to))
            ->when($this->search !== '', fn ($query) => $query->whereHas(
                'order',
                fn ($orders) => $orders
                    ->where('number', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%")
                    ->orWhere('dropoff', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30);
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.orders.order-list', [
            'deliveries' => $this->deliveries(),
            'statuses' => DeliveryStatus::cases(),
            'businesses' => Business::query()->orderBy('name')->get(['id', 'name']),
            'companies' => DeliveryCompany::query()->orderBy('name')->get(['id', 'name']),
        ], __('app.nav.orders'));
    }
}
