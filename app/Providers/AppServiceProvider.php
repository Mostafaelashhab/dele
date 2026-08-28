<?php

namespace App\Providers;

use App\Domain\Zones\ZoneResolver;
use App\Http\Middleware\EnsurePlatformStaff;
use App\Http\Middleware\ResolveBusinessTenant;
use App\Http\Middleware\ResolveCompanyTenant;
use App\Http\Middleware\ResolveRider;
use App\Http\Middleware\SetLocale;
use App\Models\Zone;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsappChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureTenancy();
        $this->configureSecurity();
        $this->configureNotifications();
        $this->configureCacheInvalidation();
    }

    /**
     * Keep the tenant resolved on Livewire's own requests.
     *
     * Livewire re-runs only the middleware it has been told is persistent,
     * and its default list covers authentication and nothing else. Every
     * portal screen here resolves its tenant in middleware and then calls
     * `companyOrFail()` / `businessOrFail()` while rendering, so without this
     * the first page load works and every subsequent interaction — a click, a
     * form submit, a `wire:poll` tick — arrives with no tenant and throws
     * "No delivery company resolved for this request."
     *
     * Registering them here is also what keeps tenancy enforced rather than
     * merely convenient: an update request that skipped `ResolveCompanyTenant`
     * would skip its suspended-account and membership checks too.
     */
    private function configureTenancy(): void
    {
        Livewire::addPersistentMiddleware([
            EnsurePlatformStaff::class,
            ResolveBusinessTenant::class,
            ResolveCompanyTenant::class,
            ResolveRider::class,
            SetLocale::class,
        ]);
    }

    /**
     * Fail loudly in development on lazy loading and on assigning attributes
     * that do not exist — both produce silent, hard-to-find bugs in
     * production if they are allowed to pass in development.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::automaticallyEagerLoadRelationships();
        Model::unguard(false);
    }

    private function configureSecurity(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->uncompromised()
            : Password::min(8));
    }

    private function configureNotifications(): void
    {
        Notification::extend('sms', fn ($app) => $app->make(SmsChannel::class));
        Notification::extend('whatsapp', fn ($app) => $app->make(WhatsappChannel::class));
    }

    /**
     * Zones are cached for matching and pricing, so any edit has to invalidate
     * that cache immediately or dispatch will keep using the old geography.
     */
    private function configureCacheInvalidation(): void
    {
        Zone::saved(fn () => ZoneResolver::flushCache());
        Zone::deleted(fn () => ZoneResolver::flushCache());
    }
}
