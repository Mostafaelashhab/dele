<?php

namespace App\Livewire\Business;

use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerAccountType;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Delivery;
use App\Models\FinancialTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * What the business has spent on delivery, and what it currently owes.
 *
 * Figures come from the ledger, not from summing delivery prices, so the
 * statement and the invoice can never disagree.
 */
class Finance extends Component
{
    use UsesPortalLayout;

    #[Url(except: 'month')]
    public string $range = 'month';

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function summary(): array
    {
        $business = app(CurrentTenant::class)->businessOrFail();
        [$from, $to] = $this->window();

        $deliveries = Delivery::query()
            ->where('business_id', $business->id)
            ->where('status', DeliveryStatus::Delivered)
            ->whereBetween('delivered_at', [$from, $to])
            ->get(['price_minor', 'distance_meters', 'created_at', 'delivered_at']);

        $averageMinutes = $deliveries
            ->map(fn (Delivery $delivery) => $delivery->totalMinutes())
            ->filter()
            ->avg();

        return [
            'count' => $deliveries->count(),
            'spend' => Money::ofMinor((int) $deliveries->sum(fn (Delivery $d) => $d->price()->minor)),
            'average' => $deliveries->isEmpty()
                ? Money::zero()
                : Money::ofMinor((int) round($deliveries->avg(fn (Delivery $d) => $d->price()->minor))),
            'average_minutes' => $averageMinutes === null ? null : (int) round($averageMinutes),
            // A business account normally sits in debit, so the balance is
            // presented as an amount owed rather than a negative number.
            'outstanding' => app(LedgerService::class)
                ->unsettledBalance(LedgerAccountType::Business, $business->id)
                ->absolute(),
        ];
    }

    /**
     * @return Collection<int, FinancialTransaction>
     */
    #[Computed]
    public function entries(): Collection
    {
        [$from, $to] = $this->window();

        return FinancialTransaction::query()
            ->forAccount(LedgerAccountType::Business, app(CurrentTenant::class)->businessOrFail()->id)
            ->occurredBetween($from, $to)
            ->with('delivery.order')
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function window(): array
    {
        return match ($this->range) {
            'today' => [today()->startOfDay(), today()->endOfDay()],
            'week' => [today()->startOfWeek(), today()->endOfDay()],
            default => [today()->startOfMonth(), today()->endOfDay()],
        };
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.finance', title: __('app.nav.finance'));
    }
}
