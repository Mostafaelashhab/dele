<?php

namespace App\Livewire\Rider;

use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerAccountType;
use App\Models\Delivery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * A rider's earnings, read from the ledger rather than recomputed.
 *
 * The number a rider sees is the same number the settlement will pay, because
 * both come from the same entries.
 */
class Earnings extends Component
{
    public string $range = 'week';

    /**
     * @return array<string, Money|int>
     */
    #[Computed]
    public function summary(): array
    {
        $rider = app(CurrentTenant::class)->riderOrFail();
        [$from, $to] = $this->window();

        $deliveries = Delivery::query()
            ->where('rider_id', $rider->id)
            ->where('status', DeliveryStatus::Delivered)
            ->whereBetween('delivered_at', [$from, $to])
            ->get(['rider_payout_minor', 'delivered_at', 'distance_meters']);

        return [
            'earned' => Money::ofMinor((int) $deliveries->sum(fn (Delivery $d) => $d->riderPayout()->minor)),
            'deliveries' => $deliveries->count(),
            'distance_km' => (int) round($deliveries->sum('distance_meters') / 1000),
            'unsettled' => app(LedgerService::class)->unsettledBalance(LedgerAccountType::Rider, $rider->id),
        ];
    }

    /**
     * @return Collection<int, array{date: string, count: int, total: Money}>
     */
    #[Computed]
    public function daily(): Collection
    {
        $rider = app(CurrentTenant::class)->riderOrFail();
        [$from, $to] = $this->window();

        return Delivery::query()
            ->where('rider_id', $rider->id)
            ->where('status', DeliveryStatus::Delivered)
            ->whereBetween('delivered_at', [$from, $to])
            ->get(['rider_payout_minor', 'delivered_at'])
            ->groupBy(fn (Delivery $delivery) => $delivery->delivered_at->toDateString())
            ->map(fn (Collection $group, string $date) => [
                'date' => $date,
                'count' => $group->count(),
                'total' => Money::ofMinor((int) $group->sum(fn (Delivery $d) => $d->riderPayout()->minor)),
            ])
            ->sortKeysDesc()
            ->values();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function window(): array
    {
        return match ($this->range) {
            'today' => [today()->startOfDay(), today()->endOfDay()],
            'month' => [today()->startOfMonth(), today()->endOfDay()],
            default => [today()->startOfWeek(), today()->endOfDay()],
        };
    }

    public function render(): View
    {
        return view('livewire.rider.earnings')
            ->layout('components.layouts.rider', ['title' => __('app.nav.earnings')]);
    }
}
