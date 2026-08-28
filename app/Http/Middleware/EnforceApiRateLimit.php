<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\ApiContext;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-client rate limiting.
 *
 * The limit is a property of the client rather than a global constant, so a
 * high-volume integration can be raised without loosening the ceiling for
 * everyone else.
 */
class EnforceApiRateLimit
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly ApiContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $client = $this->context->client();

        $key = 'api:'.($client?->id ?? 'anon:'.$request->ip());
        $limit = $client?->rateLimit() ?? (int) config('platform.api.default_rate_limit_per_minute');

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'error' => [
                    'type' => 'rate_limit_exceeded',
                    'message' => __('api.errors.rate_limited', ['seconds' => $retryAfter]),
                ],
            ], 429, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - $this->limiter->attempts($key)),
        ]);
    }
}
