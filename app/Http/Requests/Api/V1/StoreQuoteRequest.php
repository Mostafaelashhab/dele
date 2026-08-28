<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
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
            'pickup' => ['required', 'array'],
            'pickup.lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup.lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff' => ['required', 'array'],
            'dropoff.lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff.lng' => ['required', 'numeric', 'between:-180,180'],
            'priority' => ['nullable', Rule::enum(DeliveryPriority::class)],
            'package_size' => ['nullable', Rule::enum(PackageSize::class)],
            'cod_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
