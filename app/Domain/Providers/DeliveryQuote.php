<?php

namespace App\Domain\Providers;

use App\Domain\Shared\ValueObjects\Money;

/**
 * A provider's answer to "what would you charge and how long would you take".
 */
final readonly class DeliveryQuote
{
    public function __construct(
        public string $providerKey,
        public Money $price,
        public int $estimatedMinutes,
        public bool $available = true,
        public ?string $unavailableReason = null,
        public ?string $externalQuoteId = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    public static function unavailable(string $providerKey, string $reason): self
    {
        return new self(
            providerKey: $providerKey,
            price: Money::zero(),
            estimatedMinutes: 0,
            available: false,
            unavailableReason: $reason,
        );
    }
}
