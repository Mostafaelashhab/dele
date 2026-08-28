<?php

use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
use App\Http\Controllers\Api\V1\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API — v1
|--------------------------------------------------------------------------
|
| Versioned from the first release so a breaking change can ship as /v2 while
| every integration already built against /v1 keeps working untouched.
|
*/

Route::prefix('v1')->name('api.v1.')->middleware(['api.key', 'api.throttle'])->group(function (): void {

    Route::get('/me', MeController::class)->name('me');
    Route::get('/zones', ZoneController::class)->name('zones.index');

    Route::post('/quotes', QuoteController::class)->name('quotes.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Creating and cancelling are the two calls a retry could duplicate, so
    // both accept an Idempotency-Key.
    Route::middleware('api.idempotent')->group(function (): void {
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });

    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])->name('deliveries.show');
    Route::get('/deliveries/{delivery}/events', [DeliveryController::class, 'events'])->name('deliveries.events');

    Route::apiResource('webhooks', WebhookEndpointController::class)
        ->parameters(['webhooks' => 'endpoint'])
        ->except(['show']);
});

// Tracking is public by design: the token is the credential, and a customer
// following a link from an SMS has no API key.
Route::get('/v1/tracking/{token}', TrackingController::class)
    ->middleware('throttle:60,1')
    ->name('api.v1.tracking.show');
