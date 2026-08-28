<?php

namespace App\Domain\Providers;

use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Enums\DeliveryStatus;

final readonly class TrackingData
{
    public function __construct(
        public DeliveryStatus $status,
        public ?GeoPoint $riderPosition = null,
        public ?\DateTimeInterface $estimatedArrival = null,
        public ?string $riderName = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
