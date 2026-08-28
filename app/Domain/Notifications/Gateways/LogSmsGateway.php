<?php

namespace App\Domain\Notifications\Gateways;

use App\Domain\Notifications\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Default binding: writes the message to the log instead of sending it.
 *
 * This keeps every notification path exercised end to end in development and
 * on the pilot, without a provider contract or per-message cost.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message, ?string $reference = null): bool
    {
        Log::channel(config('logging.default'))->info('SMS dispatched.', [
            'to' => $this->mask($phone),
            'reference' => $reference,
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Phone numbers are personal data; the log keeps only enough to debug.
     */
    private function mask(string $phone): string
    {
        return mb_strlen($phone) > 4
            ? str_repeat('*', mb_strlen($phone) - 4).mb_substr($phone, -4)
            : $phone;
    }
}
