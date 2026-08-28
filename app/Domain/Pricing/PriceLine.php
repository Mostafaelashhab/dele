<?php

namespace App\Domain\Pricing;

use App\Domain\Shared\ValueObjects\Money;
use App\Enums\PricingComponent;
use JsonSerializable;

/**
 * One explainable line of a quote. Storing the rule that produced it is what
 * lets support answer "why did this cost 27 pounds?" months later.
 */
final readonly class PriceLine implements JsonSerializable
{
    public function __construct(
        public PricingComponent $component,
        public string $label,
        public Money $amount,
        public ?string $ruleId = null,
        /** @var array<string, mixed> */
        public array $detail = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'component' => $this->component->value,
            'label' => $this->label,
            'amount_minor' => $this->amount->minor,
            'rule_id' => $this->ruleId,
            'detail' => $this->detail,
        ];
    }
}
