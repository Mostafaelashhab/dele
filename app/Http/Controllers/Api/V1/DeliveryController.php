<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DeliveryResource;
use App\Http\Resources\Api\V1\OrderEventResource;
use App\Models\Delivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(DeliveryStatus::class)],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $deliveries = $this->scopedQuery()
            ->with(['order', 'deliveryCompany', 'rider'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['active'] ?? false, fn ($query) => $query->active())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25);

        return DeliveryResource::collection($deliveries);
    }

    public function show(string $delivery): DeliveryResource
    {
        return new DeliveryResource($this->findDelivery($delivery));
    }

    public function events(string $delivery): AnonymousResourceCollection
    {
        $model = $this->findDelivery($delivery);

        return OrderEventResource::collection(
            $model->events()->chronological()->get()
        );
    }

    /**
     * A business sees the deliveries it paid for; a delivery company sees the
     * ones it carried. Neither can widen the scope with a parameter.
     */
    private function scopedQuery(): Builder
    {
        if ($business = $this->tenant->business()) {
            return Delivery::query()->where('business_id', $business->id);
        }

        return Delivery::query()->forCompany($this->tenant->companyOrFail());
    }

    private function findDelivery(string $publicId): Delivery
    {
        return $this->scopedQuery()
            ->where('public_id', $publicId)
            ->with(['order', 'deliveryCompany', 'rider'])
            ->firstOrFail();
    }
}
