<?php

namespace App\Jobs;

use App\Models\ApiRequest;
use App\Models\DeliveryLocation;
use App\Models\IdempotencyKey;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the high-churn tables from growing without bound.
 *
 * Deleted in bounded chunks rather than one statement so the job never holds
 * a long lock on a table the rider app is actively writing to.
 */
class PruneTrackingHistoryJob implements ShouldQueue
{
    use Queueable;

    private const CHUNK = 5000;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $locations = $this->pruneInChunks(
            DeliveryLocation::query()->where('recorded_at', '<', now()->subDays(
                (int) config('platform.tracking.retention_days', 30)
            ))
        );

        $apiLogs = $this->pruneInChunks(
            ApiRequest::query()->where('created_at', '<', now()->subDays(
                (int) config('platform.api.log_retention_days', 30)
            ))
        );

        $keys = IdempotencyKey::query()->where('expires_at', '<', now())->delete();

        Log::info('Pruned tracking and API history.', [
            'locations' => $locations,
            'api_requests' => $apiLogs,
            'idempotency_keys' => $keys,
        ]);
    }

    private function pruneInChunks(Builder $query): int
    {
        $deleted = 0;

        do {
            $batch = (clone $query)->limit(self::CHUNK)->delete();
            $deleted += $batch;
        } while ($batch >= self::CHUNK);

        return $deleted;
    }
}
