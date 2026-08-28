<?php

namespace App\Domain\Orders;

use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use Illuminate\Support\Carbon;

/**
 * A validated delivery request, independent of how it arrived — a dashboard
 * form, the public API, or a future marketplace integration all build one of
 * these, so the creation path is identical for every channel.
 */
final readonly class OrderData
{
    /**
     * @param  array<int, array{name: string, quantity?: int, sku?: string|null, unit_price_minor?: int, weight_grams?: int|null, notes?: string|null}>  $items
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public LocationSnapshot $pickup,
        public LocationSnapshot $dropoff,
        public DeliveryPriority $priority = DeliveryPriority::Standard,
        public PackageSize $packageSize = PackageSize::Small,
        public PaymentType $paymentType = PaymentType::Prepaid,
        public ?Money $codAmount = null,
        public ?Money $declaredValue = null,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?int $packageWeightGrams = null,
        public ?\DateTimeInterface $scheduledFor = null,
        public ?string $customerId = null,
        public array $items = [],
        public array $metadata = [],
    ) {}

    public function cod(): Money
    {
        return $this->codAmount ?? Money::zero();
    }

    public function declared(): Money
    {
        return $this->declaredValue ?? Money::zero();
    }

    /**
     * Build from already-validated request input. Validation lives in Form
     * Requests; this only maps shapes.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromValidated(array $data): self
    {
        return new self(
            pickup: LocationSnapshot::fromArray($data['pickup']),
            dropoff: LocationSnapshot::fromArray($data['dropoff']),
            priority: DeliveryPriority::from($data['priority'] ?? DeliveryPriority::Standard->value),
            packageSize: PackageSize::from($data['package_size'] ?? PackageSize::Small->value),
            paymentType: PaymentType::from($data['payment_type'] ?? PaymentType::Prepaid->value),
            codAmount: isset($data['cod_amount_minor']) ? Money::ofMinor((int) $data['cod_amount_minor']) : null,
            declaredValue: isset($data['declared_value_minor']) ? Money::ofMinor((int) $data['declared_value_minor']) : null,
            reference: $data['reference'] ?? null,
            notes: $data['notes'] ?? null,
            packageWeightGrams: isset($data['package_weight_grams']) ? (int) $data['package_weight_grams'] : null,
            scheduledFor: isset($data['scheduled_for']) ? Carbon::parse($data['scheduled_for']) : null,
            customerId: $data['customer_id'] ?? null,
            items: $data['items'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }
}
