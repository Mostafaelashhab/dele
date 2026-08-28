<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Brings a deployed copy up to date, in an order that is safe to repeat.
 *
 * Shared hosting means no build step on the server and often no shell worth
 * the name, so this does the parts that must happen after files land: the
 * schema, the caches, and the storage link. Assets are built locally and
 * uploaded — `npm run build` needs a toolchain the host does not have.
 */
class Deploy extends Command
{
    protected $signature = 'banha:deploy
        {--fresh : Wipe and re-migrate. Destroys all data.}
        {--seed : Seed reference data after migrating}';

    protected $description = 'Migrate, cache configuration and link storage after an upload';

    public function handle(): int
    {
        if (app()->isProduction() && $this->option('fresh')) {
            $this->error('Refusing to wipe a production database.');

            return self::FAILURE;
        }

        $this->components->info('Deploying '.config('app.name'));

        if (! $this->checkEnvironment()) {
            return self::FAILURE;
        }

        // Schema first: cached config pointing at a column that does not exist
        // yet fails in a much more confusing way than a migration error.
        $this->components->task('database schema', function (): bool {
            $this->callSilently('migrate', ['--force' => true]);

            return true;
        });

        if ($this->option('seed')) {
            $this->components->task('reference data', function (): bool {
                $this->callSilently('db:seed', ['--force' => true]);

                return true;
            });
        }

        $this->components->task('public storage link', function (): bool {
            if (! File::exists(public_path('storage'))) {
                $this->callSilently('storage:link');
            }

            return true;
        });

        // Rebuilt rather than cleared: on a host with no opcache warmup, an
        // uncached config costs every visitor a file scan.
        foreach (['config' => 'config:cache', 'routes' => 'route:cache', 'views' => 'view:cache'] as $label => $command) {
            $this->components->task($label.' cached', function () use ($command): bool {
                $this->callSilently($command);

                return true;
            });
        }

        $this->newLine();
        $this->components->info('Deployed. Assets must be built locally and uploaded with public/build.');

        return self::SUCCESS;
    }

    /**
     * The mistakes that are silent until a customer finds them.
     */
    private function checkEnvironment(): bool
    {
        $problems = [];

        if (config('app.debug') && app()->isProduction()) {
            $problems[] = 'APP_DEBUG is on in production — stack traces would be public.';
        }

        if (blank(config('app.key'))) {
            $problems[] = 'APP_KEY is empty. Run php artisan key:generate --force.';
        }

        // Only a problem where it is deployed; localhost is correct locally.
        if (app()->isProduction() && str_contains((string) config('app.url'), 'localhost')) {
            $problems[] = 'APP_URL still points at localhost — links and the sitemap would be wrong.';
        }

        // Nothing runs a worker here, so a queued job would simply never run.
        if (config('queue.default') !== 'sync') {
            $problems[] = 'QUEUE_CONNECTION is not sync, but this deployment runs no worker.';
        }

        if (! File::exists(public_path('build/manifest.json'))) {
            $problems[] = 'public/build is missing. Run npm run build locally and upload it.';
        }

        if (! Schema::hasTable('migrations')) {
            $this->components->warn('No migrations table yet — this looks like a first deploy.');
        }

        foreach ($problems as $problem) {
            $this->components->error($problem);
        }

        return $problems === [];
    }
}
