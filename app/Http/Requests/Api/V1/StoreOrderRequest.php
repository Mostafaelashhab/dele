<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:64'],

            'pickup' => ['required', 'array'],
            'pickup.name' => ['required', 'string', 'max:120'],
            'pickup.phone' => ['required', 'string', 'max:20', 'regex:/^(\+?2)?01[0-2,5]\d{8}$/'],
            'pickup.address' => ['required', 'string', 'max:255'],
            'pickup.area' => ['nullable', 'string', 'max:120'],
            'pickup.city' => ['nullable', 'string', 'max:120'],
            'pickup.landmark' => ['nullable', 'string', 'max:160'],
            'pickup.lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:pickup.lng'],
            'pickup.lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:pickup.lat'],

            'dropoff' => ['required', 'array'],
            'dropoff.name' => ['required', 'string', 'max:120'],
            'dropoff.phone' => ['required', 'string', 'max:20', 'regex:/^(\+?2)?01[0-2,5]\d{8}$/'],
            'dropoff.address' => ['required', 'string', 'max:255'],
            'dropoff.area' => ['nullable', 'string', 'max:120'],
            'dropoff.city' => ['nullable', 'string', 'max:120'],
            'dropoff.landmark' => ['nullable', 'string', 'max:160'],
            'dropoff.lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:dropoff.lng'],
            'dropoff.lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:dropoff.lat'],

            'priority' => ['nullable', Rule::enum(DeliveryPriority::class)],
            'package_size' => ['nullable', Rule::enum(PackageSize::class)],
            'package_weight_grams' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'payment_type' => ['nullable', Rule::enum(PaymentType::class)],
            'cod_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'declared_value' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'scheduled_for' => ['nullable', 'date', 'after:now', 'before:'.now()->addDays(14)->toDateString()],

            'items' => ['nullable', 'array', 'max:50'],
            'items.*.name' => ['required', 'string', 'max:160'],
            'items.*.sku' => ['nullable', 'string', 'max:64'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price' => ['nullable', 'integer', 'min:0'],
            'items.*.weight_grams' => ['nullable', 'integer', 'min:0'],

            'metadata' => ['nullable', 'array', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Cash on delivery without an amount is almost always a client
            // bug, and one that would silently under-collect from a customer.
            if ($this->input('payment_type') === PaymentType::CashOnDelivery->value
                && (int) $this->input('cod_amount', 0) <= 0) {
                $validator->errors()->add('cod_amount', __('validation.required', ['attribute' => 'cod_amount']));
            }
        });
    }

    /**
     * Reshape the public API's payload into the internal OrderData contract.
     * The API's field names are part of a published promise; the domain's are
     * free to change.
     *
     * @return array<string, mixed>
     */
    public function toOrderPayload(): array
    {
        $validated = $this->validated();

        return [
            'reference' => $validated['reference'] ?? null,
            'pickup' => $this->mapLocation($validated['pickup']),
            'dropoff' => $this->mapLocation($validated['dropoff']),
            'priority' => $validated['priority'] ?? DeliveryPriority::Standard->value,
            'package_size' => $validated['package_size'] ?? PackageSize::Small->value,
            'package_weight_grams' => $validated['package_weight_grams'] ?? null,
            'payment_type' => $validated['payment_type'] ?? PaymentType::Prepaid->value,
            'cod_amount_minor' => $validated['cod_amount'] ?? 0,
            'declared_value_minor' => $validated['declared_value'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'scheduled_for' => $validated['scheduled_for'] ?? null,
            'items' => array_map(fn (array $item) => [
                'name' => $item['name'],
                'sku' => $item['sku'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit_price_minor' => $item['unit_price'] ?? 0,
                'weight_grams' => $item['weight_grams'] ?? null,
            ], $validated['items'] ?? []),
            'metadata' => $validated['metadata'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    private function mapLocation(array $location): array
    {
        return [
            'contact_name' => $location['name'],
            'contact_phone' => $this->normalisePhone($location['phone']),
            'address_line' => $location['address'],
            'area' => $location['area'] ?? null,
            'city' => $location['city'] ?? config('platform.city'),
            'landmark' => $location['landmark'] ?? null,
            'latitude' => $location['lat'] ?? null,
            'longitude' => $location['lng'] ?? null,
        ];
    }

    /**
     * Egyptian mobile numbers arrive as 01…, +201… or 002 01…; they are
     * stored in one form so a customer is one person however they were typed.
     */
    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '20')) {
            $digits = mb_substr($digits, 2);
        }

        return str_starts_with($digits, '0') ? $digits : '0'.$digits;
    }
}
