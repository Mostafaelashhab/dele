<?php

namespace App\Livewire\Business\Orders;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\OrderStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrderList extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

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
        $this->reset(['search', 'status', 'from', 'to']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->forBusiness(app(CurrentTenant::class)->businessOrFail())
            ->with(['currentDelivery.deliveryCompany', 'currentDelivery.rider'])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to))
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('number', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%")
                    ->orWhere('dropoff', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.orders.order-list', [
            'orders' => $this->orders(),
            'statuses' => OrderStatus::cases(),
        ], __('app.nav.orders'));
    }
}
