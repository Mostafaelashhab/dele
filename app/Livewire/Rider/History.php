<?php

namespace App\Livewire\Rider;

use App\Domain\Tenancy\CurrentTenant;
use App\Models\Delivery;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    /**
     * @return LengthAwarePaginator<int, Delivery>
     */
    public function deliveries(): LengthAwarePaginator
    {
        return Delivery::query()
            ->where('rider_id', app(CurrentTenant::class)->riderOrFail()->id)
            ->whereNotNull('assigned_at')
            ->with('order')
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.rider.history', ['deliveries' => $this->deliveries()])
            ->layout('components.layouts.rider', ['title' => __('app.nav.history')]);
    }
}
