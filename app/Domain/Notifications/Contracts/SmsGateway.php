<?php

namespace App\Domain\Notifications\Contracts;

/**
 * Boundary to whichever SMS aggregator the platform uses. Kept narrow so an
 * Egyptian provider can be swapped in without touching a single notification.
 */
interface SmsGateway
{
    public function send(string $phone, string $message, ?string $reference = null): bool;
}
