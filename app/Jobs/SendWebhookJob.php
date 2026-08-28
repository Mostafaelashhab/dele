<?php

namespace App\Jobs;

use App\Domain\Webhooks\WebhookSigner;
use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers one webhook, once.
 *
 * The job never schedules its own successor: it records the outcome and, when
 * another attempt is due, stamps next_attempt_at. A scheduled sweeper picks
 * those up. Keeping the retry state in the table rather than in a chain of
 * delayed jobs means the backoff is inspectable, survives a lost queue, and
 * cannot recurse when the queue runs synchronously.
 */
class SendWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly string $webhookDeliveryId,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(WebhookSigner $signer): void
    {
        $delivery = WebhookDelivery::query()->with('webhookEndpoint')->find($this->webhookDeliveryId);

        if ($delivery === null || $delivery->status !== WebhookDeliveryStatus::Pending) {
            return;
        }

        $endpoint = $delivery->webhookEndpoint;

        if ($endpoint === null || ! $endpoint->is_active || $endpoint->disabled_at !== null) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Abandoned,
                'error' => 'endpoint_inactive',
            ])->save();

            return;
        }

        $body = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $timestamp = now()->getTimestamp();
        $startedAt = microtime(true);

        try {
            $response = Http::withHeaders([
                config('platform.webhooks.signature_header') => $signer->sign($body, $endpoint->secret, $timestamp),
                config('platform.webhooks.timestamp_header') => (string) $timestamp,
                'X-Banha-Event' => $delivery->event->value,
                'X-Banha-Event-Id' => $delivery->event_id,
                'Content-Type' => 'application/json',
            ])
                ->timeout((int) config('platform.webhooks.timeout_seconds', 10))
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($response->successful()) {
                $this->markDelivered($delivery, $response->status(), $response->body(), $durationMs);

                return;
            }

            $this->scheduleRetry($delivery, $response->status(), $response->body(), $durationMs, 'http_error');
        } catch (Throwable $exception) {
            $this->scheduleRetry(
                $delivery,
                null,
                null,
                (int) round((microtime(true) - $startedAt) * 1000),
                mb_substr($exception->getMessage(), 0, 250),
            );
        }
    }

    protected function markDelivered(WebhookDelivery $delivery, int $status, string $body, int $durationMs): void
    {
        $delivery->forceFill([
            'status' => WebhookDeliveryStatus::Delivered,
            'attempts' => $delivery->attempts + 1,
            'response_status' => $status,
            'response_body' => mb_substr($body, 0, 2000),
            'duration_ms' => $durationMs,
            'delivered_at' => now(),
            'next_attempt_at' => null,
            'error' => null,
        ])->save();

        $delivery->webhookEndpoint->forceFill([
            'consecutive_failures' => 0,
            'last_success_at' => now(),
        ])->save();
    }

    protected function scheduleRetry(
        WebhookDelivery $delivery,
        ?int $status,
        ?string $body,
        int $durationMs,
        string $error,
    ): void {
        $attempts = $delivery->attempts + 1;
        $schedule = (array) config('platform.webhooks.backoff_seconds', []);
        $maxAttempts = (int) config('platform.webhooks.max_attempts', 6);

        $exhausted = $attempts >= $maxAttempts;
        $delay = $schedule[$attempts - 1] ?? end($schedule) ?: 3600;

        $delivery->forceFill([
            'status' => $exhausted ? WebhookDeliveryStatus::Failed : WebhookDeliveryStatus::Pending,
            'attempts' => $attempts,
            'response_status' => $status,
            'response_body' => $body === null ? null : mb_substr($body, 0, 2000),
            'duration_ms' => $durationMs,
            'error' => $error,
            'next_attempt_at' => $exhausted ? null : now()->addSeconds($delay),
        ])->save();

        $endpoint = $delivery->webhookEndpoint;
        $failures = $endpoint->consecutive_failures + 1;

        // An endpoint that has been failing for a long run is switched off
        // rather than retried for ever; the owner can re-enable it.
        $endpoint->forceFill([
            'consecutive_failures' => $failures,
            'last_failure_at' => now(),
            'disabled_at' => $failures >= 25 ? now() : $endpoint->disabled_at,
        ])->save();

        if (! $exhausted) {
            return;
        }

        Log::warning('Webhook delivery abandoned after exhausting retries.', [
            'webhook_delivery_id' => $delivery->id,
            'endpoint_id' => $endpoint->id,
            'event' => $delivery->event->value,
        ]);
    }
}
