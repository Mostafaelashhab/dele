<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\ApiContext;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWebhookEndpointRequest;
use App\Http\Resources\Api\V1\WebhookEndpointResource;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookEndpointController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly ApiContext $context,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return WebhookEndpointResource::collection($this->scopedQuery()->latest()->get());
    }

    public function store(StoreWebhookEndpointRequest $request): JsonResponse
    {
        $owner = $this->tenant->business() ?? $this->tenant->companyOrFail();

        $endpoint = WebhookEndpoint::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'api_client_id' => $this->context->client()?->id,
            'name' => $request->validated('name'),
            'url' => $request->validated('url'),
            'events' => $request->validated('events'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return (new WebhookEndpointResource($endpoint))->response()->setStatusCode(201);
    }

    public function update(StoreWebhookEndpointRequest $request, string $endpoint): WebhookEndpointResource
    {
        $model = $this->scopedQuery()->whereKey($endpoint)->firstOrFail();

        $model->update([
            'name' => $request->validated('name'),
            'url' => $request->validated('url'),
            'events' => $request->validated('events'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Re-enabling clears the failure counter so a fixed endpoint gets a
        // clean start rather than sitting one failure from suspension. The
        // health columns are not fillable, so they are written explicitly.
        $model->forceFill([
            'consecutive_failures' => 0,
            'disabled_at' => null,
        ])->save();

        return new WebhookEndpointResource($model);
    }

    public function destroy(string $endpoint): JsonResponse
    {
        $this->scopedQuery()->whereKey($endpoint)->firstOrFail()->delete();

        return response()->json(null, 204);
    }

    private function scopedQuery(): Builder
    {
        $owner = $this->tenant->business() ?? $this->tenant->companyOrFail();

        return WebhookEndpoint::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey());
    }
}
