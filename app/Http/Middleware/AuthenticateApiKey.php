<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\ApiContext;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\ApiClient;
use App\Models\ApiKey;
use App\Models\Business;
use App\Models\DeliveryCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token authentication for the public API.
 *
 * Keys are looked up by their public prefix and then verified against a
 * stored hash in constant time, so the database never holds a usable
 * credential and a timing side channel cannot confirm a partial guess.
 *
 * Structured as middleware rather than a guard because the API's identity is
 * a client and a tenant, not a user — there is no session to establish.
 */
class AuthenticateApiKey
{
    public function __construct(
        private readonly ApiContext $context,
        private readonly CurrentTenant $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $presented = $this->extractKey($request);

        if ($presented === null) {
            return $this->unauthorized('missing_api_key', __('api.errors.missing_key'));
        }

        [$prefix] = array_pad(explode('.', $presented, 2), 2, null);

        $key = ApiKey::query()
            ->usable()
            ->where('prefix', $prefix)
            ->with('apiClient.owner')
            ->first();

        if ($key === null || ! hash_equals($key->key_hash, hash('sha256', $presented))) {
            return $this->unauthorized('invalid_api_key', __('api.errors.invalid_key'));
        }

        $client = $key->apiClient;

        if ($client === null || ! $client->status->canAuthenticate()) {
            return $this->unauthorized('client_suspended', __('api.errors.client_suspended'));
        }

        $this->context->authenticate($client, $key);
        $this->bindTenant($client);

        $response = $next($request);

        $this->touchUsage($key, $client, $request);

        return $response;
    }

    /**
     * Accept the standard Authorization header, and X-API-Key as a
     * convenience for the POS and e-commerce plugins that cannot set it.
     */
    private function extractKey(Request $request): ?string
    {
        $bearer = $request->bearerToken();

        if (filled($bearer)) {
            return $bearer;
        }

        $header = $request->header('X-API-Key');

        return filled($header) ? $header : null;
    }

    private function bindTenant(ApiClient $client): void
    {
        $owner = $client->owner;

        if ($owner instanceof Business) {
            $this->tenant->setBusiness($owner);

            return;
        }

        if ($owner instanceof DeliveryCompany) {
            $this->tenant->setCompany($owner);
        }
    }

    /**
     * Usage timestamps are written after the response so a slow write never
     * delays the caller, and quietly so they do not churn updated_at.
     */
    private function touchUsage(ApiKey $key, ApiClient $client, Request $request): void
    {
        defer(function () use ($key, $client, $request): void {
            $key->forceFill([
                'last_used_at' => now(),
                'last_used_ip' => $request->ip(),
            ])->saveQuietly();

            $client->forceFill(['last_used_at' => now()])->saveQuietly();
        });
    }

    private function unauthorized(string $type, string $message): Response
    {
        return response()->json([
            'error' => ['type' => $type, 'message' => $message],
        ], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
