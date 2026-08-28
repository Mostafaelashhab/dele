<?php

namespace App\Domain\Notifications\Gateways;

use App\Domain\Notifications\Contracts\WhatsappGateway;
use Illuminate\Support\Facades\Log;

class LogWhatsappGateway implements WhatsappGateway
{
    public function sendTemplate(string $phone, string $template, array $parameters = []): bool
    {
        Log::info('WhatsApp template dispatched.', [
            'to' => $phone,
            'template' => $template,
            'parameters' => $parameters,
        ]);

        return true;
    }

    public function sendText(string $phone, string $message): bool
    {
        Log::info('WhatsApp message dispatched.', ['to' => $phone, 'message' => $message]);

        return true;
    }
}
