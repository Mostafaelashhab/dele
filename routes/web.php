<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterBusinessController;
use App\Http\Controllers\Auth\RegisterCompanyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Rider\LocationController;
use App\Http\Controllers\Rider\ManifestController;
use App\Http\Controllers\TrackingLookupController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', LandingController::class)->name('home');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

// The tracking token is the only credential a customer has, so the route is
// throttled to make enumerating tokens impractical.
Route::get('/track/{token}', Livewire\Tracking\TrackDelivery::class)
    ->middleware('throttle:30,1')
    ->name('tracking.show');

// Order number plus the recipient's phone, for a customer who has the receipt
// but not the SMS. Throttled hard: the order number alone is guessable, and
// the second factor is what makes the lookup safe.
Route::post('/track', TrackingLookupController::class)
    ->middleware('throttle:12,5')
    ->name('tracking.lookup');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    /*
     * Two audiences, two doors. A shop and a courier fleet are buying
     * opposite things, and the public pages pitch them separately, so
     * /register is a chooser rather than one of the two forms wearing the
     * generic name — sending a company owner to a form asking for their
     * shop category is how the funnel used to leak.
     */
    Route::view('/register', 'auth.register-choice')->name('register');

    Route::get('/register/business', [RegisterBusinessController::class, 'create'])
        ->name('register.business');
    Route::post('/register/business', [RegisterBusinessController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('/register/company', [RegisterCompanyController::class, 'create'])
        ->name('register.company');
    Route::post('/register/company', [RegisterCompanyController::class, 'store'])
        ->middleware('throttle:6,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Platform administration
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'platform.staff'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/live', Livewire\Admin\LiveOperations::class)->name('live');

    Route::get('/orders', Livewire\Admin\Orders\OrderList::class)->name('orders.index');
    Route::get('/orders/{number}', Livewire\Admin\Orders\OrderDetail::class)->name('orders.show');

    Route::get('/businesses', Livewire\Admin\Businesses\BusinessList::class)->name('businesses.index');
    Route::get('/businesses/{business}', Livewire\Admin\Businesses\BusinessDetail::class)->name('businesses.show');

    Route::get('/companies', Livewire\Admin\Companies\CompanyList::class)->name('companies.index');
    Route::get('/companies/onboard', Livewire\Admin\Companies\OnboardCompany::class)->name('companies.onboard');
    Route::get('/companies/{company}', Livewire\Admin\Companies\CompanyDetail::class)->name('companies.show');

    Route::get('/riders', Livewire\Admin\Riders\RiderList::class)->name('riders.index');
    Route::get('/zones', Livewire\Admin\Zones\ZoneManager::class)->name('zones.index');
    Route::get('/pricing', Livewire\Admin\Pricing\PricingRuleManager::class)->name('pricing.index');
    Route::get('/settlements', Livewire\Admin\Settlements\SettlementList::class)->name('settlements.index');
    Route::get('/settlements/{settlement}', Livewire\Admin\Settlements\SettlementDetail::class)->name('settlements.show');
    Route::get('/analytics', Livewire\Admin\Analytics::class)->name('analytics');
    Route::get('/audit', Livewire\Admin\AuditTrail::class)->name('audit.index');
    Route::get('/settings', Livewire\Admin\PlatformSettings::class)->name('settings.index');
});

/*
|--------------------------------------------------------------------------
| Business portal
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'tenant.business'])->prefix('app')->name('business.')->group(function (): void {
    Route::get('/', Livewire\Business\Dashboard::class)->name('dashboard');

    Route::get('/orders', Livewire\Business\Orders\OrderList::class)->name('orders.index');
    Route::get('/orders/create', Livewire\Business\Orders\CreateOrder::class)->name('orders.create');
    Route::get('/orders/{number}', Livewire\Business\Orders\OrderDetail::class)->name('orders.show');

    Route::get('/addresses', Livewire\Business\AddressBook::class)->name('addresses.index');
    Route::get('/customers', Livewire\Business\CustomerList::class)->name('customers.index');
    Route::get('/finance', Livewire\Business\Finance::class)->name('finance');
    Route::get('/team', Livewire\Business\Team::class)->name('team.index');
    Route::get('/api', Livewire\Business\ApiAccess::class)->name('api.index');
    Route::get('/settings', Livewire\Business\Settings::class)->name('settings');
});

/*
|--------------------------------------------------------------------------
| Delivery company portal
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'tenant.company'])->prefix('company')->name('company.')->group(function (): void {
    Route::get('/', Livewire\Company\Dashboard::class)->name('dashboard');

    Route::get('/offers', Livewire\Company\Offers\OfferInbox::class)->name('offers.index');
    Route::get('/offers/{offer}', Livewire\Company\Offers\OfferDetail::class)->name('offers.show');

    Route::get('/deliveries', Livewire\Company\Deliveries\DeliveryBoard::class)->name('deliveries.index');
    Route::get('/deliveries/{delivery}', Livewire\Company\Deliveries\DeliveryDetail::class)->name('deliveries.show');

    Route::get('/riders', Livewire\Company\Riders\RiderManager::class)->name('riders.index');
    Route::get('/service-areas', Livewire\Company\ServiceAreas::class)->name('service-areas');
    Route::get('/pricing', Livewire\Company\Pricing::class)->name('pricing.index');
    Route::get('/settlements', Livewire\Company\Settlements::class)->name('settlements.index');
    Route::get('/settings', Livewire\Company\Settings::class)->name('settings');
});

/*
|--------------------------------------------------------------------------
| Rider PWA
|--------------------------------------------------------------------------
*/

Route::prefix('rider')->name('rider.')->group(function (): void {
    Route::get('/manifest.webmanifest', ManifestController::class)->name('manifest');

    Route::middleware(['auth', 'tenant.rider'])->group(function (): void {
        Route::get('/', Livewire\Rider\Home::class)->name('home');
        Route::get('/deliveries/{delivery}', Livewire\Rider\DeliveryScreen::class)->name('deliveries.show');
        Route::get('/history', Livewire\Rider\History::class)->name('history');
        Route::get('/earnings', Livewire\Rider\Earnings::class)->name('earnings');

        // High-frequency endpoint: the throttle ceiling is generous enough
        // for the configured ping rate but stops a looping client.
        Route::post('/location', LocationController::class)
            ->middleware('throttle:120,1')
            ->name('location.store');
    });
});
