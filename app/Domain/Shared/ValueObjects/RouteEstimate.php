<?php

namespace App\Domain\Shared\ValueObjects;

use JsonSerializable;

/**
 * The output of a distance calculation: how far and how long, plus which
 * driver produced it so estimates stay traceable once real routing is added.
 */
final readonly class RouteEstimate implements JsonSerializable
{
    public function __construct(
        public int $distanceMeters,
        public int $durationMinutes,
        public string $source = 'haversine',
    ) {}

    public function distanceKilometres(): float
    {
        return $this->distanceMeters / 1000;
    }

    public function plusMinutes(int $minutes): self
    {
        return new self($this->distanceMeters, $this->durationMinutes + $minutes, $this->source);
    }

    /**
     * @return array{distance_meters: int, duration_minutes: int, source: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'distance_meters' => $this->distanceMeters,
            'duration_minutes' => $this->durationMinutes,
            'source' => $this->source,
        ];
    }
}
