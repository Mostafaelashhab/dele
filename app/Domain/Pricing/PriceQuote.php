<?php

namespace App\Domain\Pricing;

use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * An immutable, fully itemised price. The sum of the lines always equals the
 * total — that invariant is what makes the quote auditable.
 */
final readonly class PriceQuote implements JsonSerializable
{
    /**
     * @param  Collection<int, PriceLine>  $lines
     */
    public function __construct(
        public Collection $lines,
        public Money $total,
        public Money $platformFee,
        public Money $companyPayout,
        public Money $riderPayout,
        public int $distanceMeters,
        public int $estimatedMinutes,
        public ?string $deliveryCompanyId = null,
        /** @var array<string, mixed> */
        public array $context = [],
    ) {}

    public function currency(): string
    {
        return $this->total->currency;
    }

    /**
     * @return Collection<int, PriceLine>
     */
    public function visibleLines(): Collection
    {
        return $this->lines->reject(fn (PriceLine $line) => $line->amount->isZero());
    }

    /**
     * The stored breakdown. This is what gets written to
     * deliveries.price_breakdown and never recalculated afterwards.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'currency' => $this->currency(),
            'total_minor' => $this->total->minor,
            'platform_fee_minor' => $this->platformFee->minor,
            'company_payout_minor' => $this->companyPayout->minor,
            'rider_payout_minor' => $this->riderPayout->minor,
            'distance_meters' => $this->distanceMeters,
            'estimated_minutes' => $this->estimatedMinutes,
            'delivery_company_id' => $this->deliveryCompanyId,
            'lines' => $this->lines->map->jsonSerialize()->values()->all(),
            'context' => $this->context,
            'quoted_at' => now()->toIso8601String(),
        ];
    }
}
