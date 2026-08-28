<?php

namespace App\Jobs;

use App\Enums\DeliveryStatus;
use App\Enums\OfferStatus;
use App\Models\DeliveryCompany;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes the denormalised performance counters the matching engine reads
 * on every dispatch.
 *
 * Metrics use a rolling window rather than all-time totals, so a company that
 * has improved is not held back for ever by an old bad month — and one that
 * has slipped stops winning work on a stale reputation.
 */
class RecalculateCompanyMetricsJob implements ShouldQueue
{
    use Queueable;

    private const WINDOW_DAYS = 30;

    public function __construct(
        public readonly ?string $companyId = null,
    ) {
        $this->onQueue('metrics');
    }

    public function handle(): void
    {
        $since = now()->subDays(self::WINDOW_DAYS);

        DeliveryCompany::query()
            ->when($this->companyId, fn ($query) => $query->whereKey($this->companyId))
            ->chunkById(50, function ($companies) use ($since): void {
                foreach ($companies as $company) {
                    $this->recalculate($company, $since);
                }
            });
    }

    protected function recalculate(DeliveryCompany $company, \DateTimeInterface $since): void
    {
        $offers = $company->offers()
            ->where('offered_at', '>=', $since)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS accepted', [OfferStatus::Accepted->value])
            ->first();

        $deliveries = $company->deliveries()
            ->where('created_at', '>=', $since)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS delivered', [DeliveryStatus::Delivered->value])
            ->selectRaw('AVG(CASE WHEN picked_up_at IS NOT NULL AND accepted_at IS NOT NULL THEN '
                .$this->minutesBetween('accepted_at', 'picked_up_at').' END) AS avg_pickup_minutes')
            ->first();

        $offersTotal = (int) ($offers->total ?? 0);
        $deliveriesTotal = (int) ($deliveries->total ?? 0);

        $company->forceFill([
            'acceptance_rate_bps' => $offersTotal > 0
                ? (int) round(((int) $offers->accepted / $offersTotal) * 10000)
                : 0,
            'completion_rate_bps' => $deliveriesTotal > 0
                ? (int) round(((int) $deliveries->delivered / $deliveriesTotal) * 10000)
                : 0,
            'average_pickup_minutes' => max(1, (int) round((float) ($deliveries->avg_pickup_minutes ?? 12))),
            'completed_deliveries_count' => $company->deliveries()
                ->where('status', DeliveryStatus::Delivered)
                ->count(),
            'metrics_updated_at' => now(),
        ])->save();
    }

    /**
     * Minute difference expressed per driver, because SQLite and MySQL spell
     * date arithmetic differently and this job runs on both.
     */
    private function minutesBetween(string $from, string $to): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "(julianday({$to}) - julianday({$from})) * 1440"
            : "TIMESTAMPDIFF(MINUTE, {$from}, {$to})";
    }
}
