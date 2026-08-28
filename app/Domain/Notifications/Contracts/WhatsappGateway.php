<?php

namespace App\Domain\Notifications\Contracts;

interface WhatsappGateway
{
    /**
     * @param  array<int, string>  $parameters
     */
    public function sendTemplate(string $phone, string $template, array $parameters = []): bool;

    public function sendText(string $phone, string $message): bool;
}
