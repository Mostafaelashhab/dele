<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\ApiContext;
use App\Models\ApiRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every API call for support and billing.
 *
 * The body is summarised, never stored verbatim: request payloads carry
 * customer names, phone numbers and addresses, and an access log is the wrong
 * place to accumulate them.
 */
class LogApiRequest
{
    public function __construct(
        private readonly ApiContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        defer(fn () => ApiRequest::create([
            'api_client_id' => $this->context->client()?->id,
            'api_key_id' => $this->context->key()?->id,
            'method' => $request->method(),
            'path' => mb_substr($request->path(), 0, 255),
            'route_name' => $request->route()?->getName(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'idempotency_key' => $request->header('Idempotency-Key'),
            'request_id' => $this->context->requestId(),
            'request_summary' => $this->summarise($request),
            'error' => $this->errorFrom($response),
        ]));

        return $response->withHeaders(['X-Request-Id' => $this->context->requestId()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summarise(Request $request): array
    {
        return [
            'query' => array_keys($request->query()),
            'body_keys' => is_array($request->json()->all()) ? array_keys($request->json()->all()) : [],
            'content_length' => (int) $request->header('Content-Length', '0'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function errorFrom(Response $response): ?array
    {
        if ($response->getStatusCode() < 400) {
            return null;
        }

        $decoded = json_decode($response->getContent() ?: 'null', true);

        return is_array($decoded) ? ($decoded['error'] ?? null) : null;
    }
}
