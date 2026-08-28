<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\ApiContext;
use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes unsafe API calls replay-safe.
 *
 * A shop's POS retrying a timed-out request must not create a second
 * delivery. The first call records its response against the key; a repeat
 * with the same body gets that stored response back, and a repeat with a
 * *different* body is rejected rather than silently served the old one —
 * that mismatch is a client bug worth surfacing.
 */
class HandleIdempotency
{
    private const HEADER = 'Idempotency-Key';

    public function __construct(
        private readonly ApiContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::HEADER);

        if (blank($key) || ! $this->context->isAuthenticated()) {
            return $next($request);
        }

        $client = $this->context->clientOrFail();
        $hash = hash('sha256', $request->getContent() ?: '{}');
        $endpoint = $request->method().' '.$request->path();

        $existing = IdempotencyKey::query()
            ->where('api_client_id', $client->id)
            ->where('key', $key)
            ->first();

        if ($existing !== null) {
            return $this->replay($existing, $hash);
        }

        try {
            $record = IdempotencyKey::create([
                'api_client_id' => $client->id,
                'key' => $key,
                'endpoint' => $endpoint,
                'request_hash' => $hash,
                'locked_at' => now(),
                'expires_at' => now()->addHours((int) config('platform.api.idempotency_ttl_hours', 24)),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two identical requests arrived at once; the loser waits for the
            // winner's response rather than duplicating the work.
            return response()->json([
                'error' => [
                    'type' => 'idempotency_in_progress',
                    'message' => __('api.errors.idempotency_in_progress'),
                ],
            ], 409);
        }

        $response = $next($request);

        $this->store($record, $response);

        return $response->withHeaders(['Idempotency-Key' => $key]);
    }

    private function replay(IdempotencyKey $record, string $hash): Response
    {
        if (! $record->matchesRequest($hash)) {
            return response()->json([
                'error' => [
                    'type' => 'idempotency_key_reused',
                    'message' => __('api.errors.idempotency_mismatch'),
                ],
            ], 422);
        }

        if (! $record->isCompleted()) {
            return response()->json([
                'error' => [
                    'type' => 'idempotency_in_progress',
                    'message' => __('api.errors.idempotency_in_progress'),
                ],
            ], 409);
        }

        return response()->json($record->response_body, $record->response_status ?? 200, [
            'Idempotency-Key' => $record->key,
            'Idempotent-Replay' => 'true',
        ]);
    }

    private function store(IdempotencyKey $record, Response $response): void
    {
        // Only successful outcomes are memoised: a caller retrying after a
        // server error should get a real second attempt, not a cached 500.
        if ($response->getStatusCode() >= 500) {
            $record->delete();

            return;
        }

        $record->forceFill([
            'response_status' => $response->getStatusCode(),
            'response_body' => json_decode($response->getContent() ?: 'null', true),
            'resource_id' => data_get(json_decode($response->getContent() ?: '{}', true), 'data.id'),
            'completed_at' => now(),
        ])->save();
    }
}
