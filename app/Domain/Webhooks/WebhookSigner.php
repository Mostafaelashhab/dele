<?php

namespace App\Domain\Webhooks;

/**
 * Signs and verifies webhook payloads.
 *
 * The timestamp is inside the signed string, not just alongside it, so a
 * captured request cannot be replayed later with a fresh timestamp header.
 */
class WebhookSigner
{
    public function sign(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= now()->getTimestamp();

        return hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }

    public function verify(
        string $payload,
        string $signature,
        string $secret,
        int $timestamp,
        ?int $toleranceSeconds = null,
    ): bool {
        $tolerance = $toleranceSeconds ?? (int) config('platform.webhooks.tolerance_seconds', 300);

        if (abs(now()->getTimestamp() - $timestamp) > $tolerance) {
            return false;
        }

        return hash_equals($this->sign($payload, $secret, $timestamp), $signature);
    }
}
