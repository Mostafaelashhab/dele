<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WebhookEndpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookEndpoint
 */
class WebhookEndpointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'object' => 'webhook_endpoint',
            'url' => $this->url,
            'events' => $this->events,
            'is_active' => $this->is_active,
            'disabled_at' => $this->disabled_at?->toIso8601String(),
            'consecutive_failures' => $this->consecutive_failures,
            'last_success_at' => $this->last_success_at?->toIso8601String(),

            // The signing secret is returned once, when the endpoint is
            // created, and never again.
            'secret' => $this->when(
                $this->wasRecentlyCreated || $request->attributes->get('reveal_webhook_secret') === true,
                fn () => $this->secret,
            ),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
