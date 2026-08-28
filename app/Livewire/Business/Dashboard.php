<?php

namespace App\Livewire\Business;

use App\Domain\Ledger\LedgerService;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerAccountType;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Business;
use App\Models\Delivery;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    use UsesPortalLayout;

    private function business(): Business
    {
        return app(CurrentTenant::class)->businessOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function metrics(): array
    {
        $business = $this->business();

        $today = Delivery::query()
            ->where('business_id', $business->id)
            ->whereDate('created_at', today())
            ->get(['status', 'price_minor', 'created_at', 'delivered_at']);

        $delivered = $today->where('status', DeliveryStatus::Delivered);

        // Average measured over completed deliveries only; including in-flight
        // ones would make the number drift down all day and mean nothing.
        $averageMinutes = $delivered
            ->map(fn (Delivery $delivery) => $delivery->totalMinutes())
            ->filter()
            ->avg();

        return [
            'today' => $today->count(),
            'completed' => $delivered->count(),
            'active' => $today->filter(fn (Delivery $d) => $d->status->isActive())->count(),
            'failed' => $today->whereIn('status', [DeliveryStatus::Failed, DeliveryStatus::Expired])->count(),
            'cost' => Money::ofMinor((int) $delivered->sum(fn (Delivery $d) => $d->price()->minor)),
            'average_minutes' => $averageMinutes === null ? null : (int) round($averageMinutes),
            // Counted off the fully hydrated active set, not the partial
            // select above — estimatedArrival() reads columns that query
            // does not fetch.
            'late' => $this->activeDeliveries->filter(fn (Delivery $d) => $d->isLate())->count(),
            'outstanding' => app(LedgerService::class)
                ->unsettledBalance(LedgerAccountType::Business, $business->id),
        ];
    }

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function activeDeliveries(): Collection
    {
        return Delivery::query()
            ->where('business_id', $this->business()->id)
            ->active()
            ->with(['order', 'deliveryCompany', 'rider'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    /**
     * Every active delivery, for the map.
     *
     * Separate from `activeDeliveries` because that one is capped at ten for
     * the table — a map that silently omitted the eleventh parcel would be
     * worse than no map.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function mapMarkers(): array
    {
        $deliveries = Delivery::query()
            ->where('business_id', $this->business()->id)
            ->active()
            ->with(['order', 'business'])
            ->get();

        return MapPayload::deliveries($deliveries, 'business.orders.show');
    }

    /**
     * A fortnight of outcomes, shaped for the column chart.
     *
     * Two weeks is the window a shop owner actually reasons about — long
     * enough to see a pattern, short enough that every column is a day they
     * can remember.
     *
     * @return array<int, array{label: string, full: string, values: array<string, int>}>
     */
    #[Computed]
    public function dailyRows(): array
    {
        $from = today()->subDays(13)->startOfDay();

        $grouped = Delivery::query()
            ->where('business_id', $this->business()->id)
            ->where('created_at', '>=', $from)
            ->get(['status', 'created_at'])
            ->groupBy(fn (Delivery $delivery) => $delivery->created_at->toDateString());

        return collect(Carbon::parse($from)->toPeriod(today())->toArray())
            ->map(function (Carbon $day) use ($grouped): array {
                $rows = $grouped->get($day->toDateString(), collect());

                return [
                    'label' => $day->translatedFormat('j'),
                    'full' => $day->translatedFormat('D j M'),
                    'values' => [
                        'delivered' => $rows->where('status', DeliveryStatus::Delivered)->count(),
                        'failed' => $rows->whereIn('status', [
                            DeliveryStatus::Failed, DeliveryStatus::Expired,
                        ])->count(),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, Delivery>
     */
    #[Computed]
    public function recent(): Collection
    {
        return Delivery::query()
            ->where('business_id', $this->business()->id)
            ->whereIn('status', [DeliveryStatus::Delivered->value, DeliveryStatus::Failed->value])
            ->with(['order', 'deliveryCompany'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.dashboard', title: __('app.nav.dashboard'));
    }
}
