<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Pricing\PriceQuote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PriceQuote
 */
class QuoteResource extends JsonResource
{
    /**
     * The platform's own fee split is not part of a quote: what a business
     * needs to know is the price, the distance and the time.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PriceQuote $quote */
        $quote = $this->resource;

        return [
            'object' => 'quote',
            'price' => $quote->total->minor,
            'currency' => $quote->currency(),
            'distance_meters' => $quote->distanceMeters,
            'estimated_minutes' => $quote->estimatedMinutes,
            'breakdown' => $quote->visibleLines()->map(fn ($line) => [
                'component' => $line->component->value,
                'label' => $line->label,
                'amount' => $line->amount->minor,
            ])->values(),
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ];
    }
}
