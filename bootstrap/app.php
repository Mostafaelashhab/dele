<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnforceApiRateLimit;
use App\Http\Middleware\EnsurePlatformStaff;
use App\Http\Middleware\HandleIdempotency;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\ResolveBusinessTenant;
use App\Http\Middleware\ResolveCompanyTenant;
use App\Http\Middleware\ResolveRider;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SweepDueWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            // With no cron and no worker, ordinary traffic is what makes
            // time-due dispatch work happen. See SweepDueWork.
            SweepDueWork::class,
        ]);

        // The API is stateless and key-authenticated: every request carries
        // its own credential, is logged, and is rate limited per client.
        $middleware->api(prepend: [
            LogApiRequest::class,
        ]);

        $middleware->alias([
            'platform.staff' => EnsurePlatformStaff::class,
            'tenant.business' => ResolveBusinessTenant::class,
            'tenant.company' => ResolveCompanyTenant::class,
            'tenant.rider' => ResolveRider::class,
            'api.key' => AuthenticateApiKey::class,
            'api.throttle' => EnforceApiRateLimit::class,
            'api.idempotent' => HandleIdempotency::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // API errors share one envelope so an integrator can parse failures
        // the same way regardless of which layer produced them.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'type' => 'validation_failed',
                        'message' => $e->getMessage(),
                        'fields' => $e->errors(),
                    ],
                ], 422);
            }

            if ($e instanceof ModelNotFoundException
                || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => ['type' => 'not_found', 'message' => __('api.errors.not_found')],
                ], 404);
            }

            if ($e instanceof AuthorizationException
                || $e instanceof AccessDeniedHttpException) {
                return response()->json([
                    'error' => ['type' => 'forbidden', 'message' => __('api.errors.forbidden')],
                ], 403);
            }

            return null;
        });
    })->create();
