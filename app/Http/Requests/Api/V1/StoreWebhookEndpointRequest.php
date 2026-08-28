<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\WebhookEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookEndpointRequest extends FormRequest
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
            'url' => [
                'required',
                'url:https',
                'max:500',
                // Blocking loopback and link-local targets keeps the platform
                // from being used to probe its own internal network.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = parse_url((string) $value, PHP_URL_HOST) ?: '';
                    $resolved = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

                    $isPublic = filter_var(
                        $resolved,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    );

                    if ($resolved === $host && ! filter_var($host, FILTER_VALIDATE_IP)) {
                        return;
                    }

                    if ($isPublic === false) {
                        $fail(__('validation.url', ['attribute' => $attribute]));
                    }
                },
            ],
            'name' => ['nullable', 'string', 'max:120'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in(WebhookEvent::values())],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
