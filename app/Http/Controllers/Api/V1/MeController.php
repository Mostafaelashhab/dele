<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\ApiContext;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Lets an integrator confirm which account and scopes their key resolves to —
 * the first call anybody makes when a key is not behaving as expected.
 */
class MeController extends Controller
{
    public function __invoke(ApiContext $context, CurrentTenant $tenant): JsonResponse
    {
        $client = $context->clientOrFail();
        $owner = $tenant->business() ?? $tenant->company();

        return response()->json([
            'data' => [
                'object' => 'api_client',
                'name' => $client->name,
                'environment' => $client->environment,
                'scopes' => $client->scopes ?? ['*'],
                'rate_limit_per_minute' => $client->rateLimit(),
                'owner' => [
                    'type' => $tenant->business() !== null ? 'business' : 'delivery_company',
                    'name' => $owner?->name,
                ],
                'api_version' => 'v1',
                'currency' => config('platform.currency.code'),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }
}
