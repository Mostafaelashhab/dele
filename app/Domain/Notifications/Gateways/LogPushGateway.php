<?php

namespace App\Domain\Notifications\Gateways;

use App\Domain\Notifications\Contracts\PushGateway;
use Illuminate\Support\Facades\Log;

class LogPushGateway implements PushGateway
{
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        Log::info('Push notification dispatched.', [
            'token' => mb_substr($deviceToken, 0, 12).'…',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        return true;
    }
}
