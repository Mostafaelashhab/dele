<?php

namespace App\Domain\Shared\ValueObjects;

use JsonSerializable;

/**
 * A frozen copy of a pickup or dropoff point, captured at order time.
 *
 * Deliveries must remain readable years later even if the underlying address
 * record is edited or deleted, so the snapshot is stored on the order itself
 * rather than resolved through a foreign key at read time.
 */
final readonly class LocationSnapshot implements JsonSerializable
{
    public function __construct(
        public string $contactName,
        public string $contactPhone,
        public string $addressLine,
        public ?string $area = null,
        public ?string $city = null,
        public ?string $landmark = null,
        public ?string $notes = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $zoneId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            contactName: (string) ($data['contact_name'] ?? ''),
            contactPhone: (string) ($data['contact_phone'] ?? ''),
            addressLine: (string) ($data['address_line'] ?? ''),
            area: $data['area'] ?? null,
            city: $data['city'] ?? null,
            landmark: $data['landmark'] ?? null,
            notes: $data['notes'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            zoneId: $data['zone_id'] ?? null,
        );
    }

    public function point(): ?GeoPoint
    {
        return GeoPoint::tryMake($this->latitude, $this->longitude);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function fullAddress(): string
    {
        return collect([$this->addressLine, $this->area, $this->city])
            ->filter()
            ->implode('، ');
    }

    /**
     * A version safe to expose on the public tracking page: no phone number,
     * no free-text notes that might contain internal instructions.
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'area' => $this->area,
            'city' => $this->city,
            'landmark' => $this->landmark,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contact_name' => $this->contactName,
            'contact_phone' => $this->contactPhone,
            'address_line' => $this->addressLine,
            'area' => $this->area,
            'city' => $this->city,
            'landmark' => $this->landmark,
            'notes' => $this->notes,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'zone_id' => $this->zoneId,
        ];
    }
}
