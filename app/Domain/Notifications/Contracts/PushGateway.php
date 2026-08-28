<?php

namespace App\Domain\Notifications\Contracts;

interface PushGateway
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool;
}
