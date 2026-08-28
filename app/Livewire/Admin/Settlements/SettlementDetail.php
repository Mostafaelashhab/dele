<?php

namespace App\Livewire\Admin\Settlements;

use App\Actions\Settlements\GenerateSettlementsAction;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\FinancialTransaction;
use App\Models\Settlement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettlementDetail extends Component
{
    use UsesPortalLayout;

    public string $settlementId = '';

    public string $paymentReference = '';

    public function mount(string $settlement): void
    {
        $this->settlementId = Settlement::query()
            ->where('reference', $settlement)
            ->firstOrFail()
            ->id;
    }

    #[Computed]
    public function settlement(): Settlement
    {
        return Settlement::query()->whereKey($this->settlementId)->firstOrFail();
    }

    /**
     * @return Collection<int, FinancialTransaction>
     */
    #[Computed]
    public function entries(): Collection
    {
        return $this->settlement()
            ->transactions()
            ->with('delivery.order')
            ->orderBy('occurred_at')
            ->get();
    }

    public function markPaid(GenerateSettlementsAction $action): void
    {
        $action->markPaid(
            $this->settlement(),
            auth()->user(),
            $this->paymentReference ?: null,
        );

        unset($this->settlement, $this->entries);

        session()->flash('status', __('finance.settlement.paid'));
    }

    public function render(): View
    {
        return $this->portalView(
            'livewire.admin.settlements.settlement-detail',
            title: $this->settlement()->reference,
        );
    }
}
