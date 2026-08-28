<?php

namespace App\Console\Commands;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookDelivery;
use Illuminate\Console\Command;

/**
 * Dispatches webhook deliveries whose next attempt has come due.
 *
 * Retry timing lives in the webhook_deliveries table rather than in a chain
 * of delayed queue jobs, so a lost or drained queue never silently abandons a
 * customer's endpoint — the next sweep simply picks it up again.
 */
class RetryDueWebhooks extends Command
{
    protected $signature = 'banha:webhooks-retry {--limit=200 : Maximum deliveries to dispatch in one sweep}';

    protected $description = 'Dispatch webhook deliveries that are due for another attempt';

    public function handle(): int
    {
        $due = WebhookDelivery::query()
            ->due()
            ->whereHas('webhookEndpoint', fn ($query) => $query
                ->where('is_active', true)
                ->whereNull('disabled_at'))
            ->orderBy('next_attempt_at')
            ->limit((int) $this->option('limit'))
            ->get(['id']);

        foreach ($due as $delivery) {
            SendWebhookJob::dispatch($delivery->id);
        }

        $this->info("Dispatched {$due->count()} due webhook deliveries.");

        return self::SUCCESS;
    }
}
