<?php

namespace App\Livewire\Admin\Settlements;

use App\Actions\Settlements\GenerateSettlementsAction;
use App\Enums\SettlementStatus;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Settlement;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SettlementList extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(except: '')]
    public string $status = '';

    public string $periodStart = '';

    public string $periodEnd = '';

    public function mount(): void
    {
        $this->periodStart = today()->subWeek()->startOfWeek()->toDateString();
        $this->periodEnd = today()->subWeek()->endOfWeek()->toDateString();
    }

    /**
     * @return LengthAwarePaginator<int, Settlement>
     */
    public function settlements(): LengthAwarePaginator
    {
        return Settlement::query()
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('period_end')
            ->orderBy('party_type')
            ->paginate(25);
    }

    /**
     * Build the period's settlements for every party with unsettled entries.
     */
    public function generate(GenerateSettlementsAction $action): void
    {
        $this->validate([
            'periodStart' => ['required', 'date'],
            'periodEnd' => ['required', 'date', 'after_or_equal:periodStart'],
        ]);

        $created = $action->handle(
            from: Carbon::parse($this->periodStart)->startOfDay(),
            to: Carbon::parse($this->periodEnd)->endOfDay(),
            generatedBy: auth()->user(),
        );

        session()->flash('status', __('audit.description.settlement_created', [
            'reference' => $created->count(),
        ]));

        $this->resetPage();
    }

    public function markPaid(string $id, GenerateSettlementsAction $action): void
    {
        $action->markPaid(Settlement::findOrFail($id), auth()->user());

        $this->resetPage();
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.settlements.settlement-list', [
            'settlements' => $this->settlements(),
            'statuses' => SettlementStatus::cases(),
        ], __('app.nav.settlements'));
    }
}
