<?php

use App\Jobs\PruneTrackingHistoryJob;
use App\Jobs\RecalculateCompanyMetricsJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| The platform is designed to run on shared hosting, where there is no
| Supervisor to keep a queue worker alive and no Redis to coordinate one. A
| single cron entry drives everything:
|
|     * * * * * cd /home/USER/app && php artisan schedule:run >> /dev/null 2>&1
|
| The scheduler then starts a short-lived worker each minute. Because the
| worker exits when the queue drains and always before the next minute, two
| workers never overlap and no process is left running between ticks.
|
*/

Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3 --queue=dispatch,events,notifications,webhooks,finance,metrics,default,maintenance')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground()
    ->description('Drain the queue on shared hosting, where no long-lived worker exists.');

// Failed jobs are retried on a slow cadence rather than abandoned: a webhook
// or a ledger posting that failed on a transient error deserves another go.
Schedule::command('queue:retry all')
    ->hourly()
    ->withoutOverlapping();

// Dispatch reads these counters on every match, so they must not go stale.
Schedule::job(new RecalculateCompanyMetricsJob)
    ->hourly()
    ->withoutOverlapping()
    ->description('Refresh delivery company performance metrics.');

Schedule::job(new PruneTrackingHistoryJob)
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->description('Prune GPS breadcrumbs, API logs and expired idempotency keys.');

// Webhook retries are driven from the table, not from delayed jobs, so a
// sweep is what actually re-attempts a customer's failing endpoint.
Schedule::command('banha:webhooks-retry')
    ->everyMinute()
    ->withoutOverlapping()
    ->description('Re-attempt webhook deliveries that are due.');

// Event-driven dispatch is the norm; this only catches deliveries whose
// queue job was lost, which shared hosting makes a real possibility.
Schedule::command('banha:dispatch-stalled')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->description('Re-dispatch deliveries that stalled without an open offer.');

Schedule::command('model:prune')
    ->dailyAt('03:45');
