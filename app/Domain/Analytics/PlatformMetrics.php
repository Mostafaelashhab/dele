<?php

namespace App\Domain\Analytics;

use App\Domain\Shared\ValueObjects\Money;
use App\Enums\AccountStatus;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerAccountType;
use App\Enums\OfferStatus;
use App\Enums\RiderStatus;
use App\Enums\TransactionCategory;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\DeliveryOffer;
use App\Models\FinancialTransaction;
use App\Models\Rider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Network-wide numbers for the platform dashboard and analytics pages.
 *
 * Deliberately a service rather than logic inside components: the same
 * definition of "average delivery time" has to hold whether it is read on a
 * dashboard, in a report, or by a future API, and defining it twice is how
 * two dashboards start disagreeing.
 */
class PlatformMetrics
{
    /**
     * Headline figures for a window.
     *
     * @return array<string, mixed>
     */
    public function overview(Carbon $from, Carbon $to): array
    {
        $deliveries = Delivery::query()
            ->whereBetween('created_at', [$from, $to])
            ->get([
                'status', 'price_minor', 'platform_fee_minor', 'created_at', 'delivered_at',
                'confirmation_code_verified_at', 'proof_photo_path',
            ]);

        $delivered = $deliveries->where('status', DeliveryStatus::Delivered);

        $durations = $delivered
            ->map(fn (Delivery $delivery) => $delivery->totalMinutes())
            ->filter();

        return [
            'orders' => $deliveries->count(),
            'active' => Delivery::query()->active()->count(),
            'delivered' => $delivered->count(),
            'failed' => $deliveries->whereIn('status', [
                DeliveryStatus::Failed, DeliveryStatus::Expired,
            ])->count(),
            'cancelled' => $deliveries->where('status', DeliveryStatus::Cancelled)->count(),

            'volume' => Money::ofMinor((int) $delivered->sum(fn (Delivery $d) => $d->price()->minor)),
            'platform_fees' => $this->platformRevenue($from, $to),

            'online_riders' => Rider::query()->where('status', RiderStatus::Online)->count(),
            'busy_riders' => Rider::query()->where('status', RiderStatus::Busy)->count(),
            'active_companies' => DeliveryCompany::query()->where('status', AccountStatus::Active)->count(),
            'active_businesses' => Business::query()->where('status', AccountStatus::Active)->count(),

            'average_minutes' => $durations->isEmpty() ? null : (int) round($durations->avg()),
            'average_price' => $delivered->isEmpty()
                ? Money::zero()
                : Money::ofMinor((int) round($delivered->avg(fn (Delivery $d) => $d->price()->minor))),

            // The share of completed deliveries that actually carry
            // evidence. This is the guarantee the platform sells, so it is
            // measured rather than assumed.
            'proof_rate' => $delivered->isEmpty()
                ? null
                : $delivered->filter(fn (Delivery $d) => $d->hasProofOfDelivery())->count()
                    / max(1, $delivered->count()),

            // Companies that signed themselves up and are waiting on a human.
            'pending_companies' => DeliveryCompany::query()
                ->where('status', AccountStatus::Pending)
                ->count(),

            'acceptance_rate' => $this->acceptanceRate($from, $to),
            'completion_rate' => $deliveries->isEmpty()
                ? null
                : $delivered->count() / max(1, $deliveries->count()),

            // Deliveries that exhausted every dispatch round: the clearest
            // signal of where the network needs more supply.
            'supply_gaps' => Delivery::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', DeliveryStatus::Failed)
                ->where('failure_reason', 'no_company_available')
                ->count(),
        ];
    }

    /**
     * The platform's own earnings, taken from the ledger rather than
     * recomputed from delivery rows.
     */
    public function platformRevenue(Carbon $from, Carbon $to): Money
    {
        $minor = FinancialTransaction::query()
            ->where('account_type', LedgerAccountType::Platform)
            ->where('category', TransactionCategory::PlatformFee)
            ->occurredBetween($from, $to)
            ->sum('amount_minor');

        return Money::ofMinor((int) $minor);
    }

    public function acceptanceRate(Carbon $from, Carbon $to): ?float
    {
        $offers = DeliveryOffer::query()
            ->whereBetween('offered_at', [$from, $to])
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS accepted', [OfferStatus::Accepted->value])
            ->first();

        $total = (int) ($offers->total ?? 0);

        return $total === 0 ? null : (int) $offers->accepted / $total;
    }

    /**
     * Daily delivery counts, used for the trend strip.
     *
     * @return Collection<int, array{date: string, delivered: int, failed: int, volume: Money}>
     */
    public function dailySeries(Carbon $from, Carbon $to): Collection
    {
        $rows = Delivery::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['status', 'price_minor', 'created_at'])
            ->groupBy(fn (Delivery $delivery) => $delivery->created_at->toDateString());

        return collect(Carbon::parse($from)->toPeriod($to)->toArray())
            ->map(function (Carbon $day) use ($rows): array {
                $group = $rows->get($day->toDateString(), collect());

                return [
                    'date' => $day->toDateString(),
                    'delivered' => $group->where('status', DeliveryStatus::Delivered)->count(),
                    'failed' => $group->whereIn('status', [
                        DeliveryStatus::Failed, DeliveryStatus::Expired,
                    ])->count(),
                    'volume' => Money::ofMinor(
                        (int) $group->where('status', DeliveryStatus::Delivered)
                            ->sum(fn (Delivery $d) => $d->price()->minor)
                    ),
                ];
            })
            ->values();
    }

    /**
     * Per-company league table.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function companyPerformance(Carbon $from, Carbon $to): Collection
    {
        return DeliveryCompany::query()
            ->with(['deliveries' => fn ($query) => $query->whereBetween('created_at', [$from, $to])])
            ->where('status', AccountStatus::Active)
            ->get()
            ->map(function (DeliveryCompany $company): array {
                $deliveries = $company->deliveries;
                $delivered = $deliveries->where('status', DeliveryStatus::Delivered);

                $durations = $delivered
                    ->map(fn (Delivery $delivery) => $delivery->totalMinutes())
                    ->filter();

                return [
                    'company' => $company,
                    'orders' => $deliveries->count(),
                    'delivered' => $delivered->count(),
                    'failed' => $deliveries->whereIn('status', [
                        DeliveryStatus::Failed, DeliveryStatus::Expired,
                    ])->count(),
                    'payout' => Money::ofMinor(
                        (int) $delivered->sum(fn (Delivery $d) => $d->companyPayout()->minor)
                    ),
                    'average_minutes' => $durations->isEmpty() ? null : (int) round($durations->avg()),
                    'acceptance_rate' => $company->acceptanceRate(),
                ];
            })
            ->sortByDesc('delivered')
            ->values();
    }

    /**
     * Per-business volume, used to see who the network actually serves.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function businessVolume(Carbon $from, Carbon $to, int $limit = 15): Collection
    {
        return Business::query()
            ->withCount(['deliveries as period_deliveries' => fn ($query) => $query
                ->whereBetween('created_at', [$from, $to])])
            ->withSum(
                ['deliveries as period_volume' => fn ($query) => $query
                    ->whereBetween('created_at', [$from, $to])
                    ->where('status', DeliveryStatus::Delivered)],
                'price_minor'
            )
            ->orderByDesc('period_deliveries')
            ->limit($limit)
            ->get()
            ->map(fn (Business $business) => [
                'business' => $business,
                'deliveries' => (int) $business->period_deliveries,
                'volume' => Money::ofMinor((int) ($business->period_volume ?? 0)),
            ]);
    }
}
