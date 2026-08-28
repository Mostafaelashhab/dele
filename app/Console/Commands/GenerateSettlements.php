<?php

namespace App\Console\Commands;

use App\Actions\Settlements\GenerateSettlementsAction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Builds settlements for a period from the command line.
 *
 * Kept as a command rather than only a button so the run is scriptable,
 * repeatable, and visible in cron output when the finance cadence is
 * eventually automated.
 */
class GenerateSettlements extends Command
{
    protected $signature = 'banha:settlements
                            {--from= : Period start (Y-m-d), defaults to last week}
                            {--to= : Period end (Y-m-d), defaults to last week}';

    protected $description = 'Roll unsettled ledger entries into per-party settlements';

    public function handle(GenerateSettlementsAction $action): int
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : today()->subWeek()->startOfWeek();

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : today()->subWeek()->endOfWeek();

        if ($to->lessThan($from)) {
            $this->error('The end of the period cannot fall before its start.');

            return self::FAILURE;
        }

        $this->info("Settling {$from->toDateString()} → {$to->toDateString()}…");

        $settlements = $action->handle($from, $to);

        if ($settlements->isEmpty()) {
            $this->line('No unsettled entries in this period.');

            return self::SUCCESS;
        }

        $this->table(
            ['Reference', 'Party', 'Deliveries', 'Net'],
            $settlements->map(fn ($settlement) => [
                $settlement->reference,
                $settlement->party()?->name ?? $settlement->party_id,
                $settlement->deliveries_count,
                $settlement->netPayable()->format(),
            ])->all(),
        );

        return self::SUCCESS;
    }
}
