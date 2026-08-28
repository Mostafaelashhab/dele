<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Deliveries\CancelDeliveryAction;
use App\Actions\Orders\CreateOrderAction;
use App\Domain\Deliveries\Actor;
use App\Domain\Orders\OrderData;
use App\Domain\Tenancy\ApiContext;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * The order endpoint an integrating shop actually uses.
 *
 * Every query is scoped to the authenticated client's own business by the
 * tenant resolved in middleware — there is no code path here that can read
 * across tenants, and no parameter that could ask it to.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly ApiContext $context,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'reference' => ['nullable', 'string', 'max:64'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()
            ->forBusiness($this->tenant->businessOrFail())
            ->with(['currentDelivery.deliveryCompany', 'currentDelivery.rider', 'items'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['reference'] ?? null, fn ($query, $reference) => $query->where('reference', $reference))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25);

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request, CreateOrderAction $createOrder): JsonResponse
    {
        $business = $this->tenant->businessOrFail();

        abort_unless($business->canOperate(), 403, __('api.errors.business_inactive'));

        $order = $createOrder->handle(
            business: $business,
            data: OrderData::fromValidated($request->toOrderPayload()),
            apiClientId: $this->context->client()?->id,
        );

        return (new OrderResource($order->load(['currentDelivery', 'items'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $order): OrderResource
    {
        return new OrderResource($this->findOrder($order));
    }

    public function cancel(Request $request, string $order, CancelDeliveryAction $cancelDelivery): OrderResource
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        $model = $this->findOrder($order);
        $delivery = $model->currentDelivery;

        abort_if(
            $delivery === null || ! $delivery->isCancellable(),
            409,
            __('api.errors.not_cancellable'),
        );

        $cancelDelivery->handle(
            delivery: $delivery,
            reason: $validated['reason'] ?? 'cancelled_by_business',
            actor: Actor::api(
                $this->context->clientOrFail()->id,
                $this->context->clientOrFail()->name,
            ),
            cancelledBy: 'business',
        );

        return new OrderResource($model->fresh(['currentDelivery', 'items']));
    }

    /**
     * Accepts either the platform's order number or the business's own
     * reference, because an integrator usually only has the latter to hand.
     */
    private function findOrder(string $identifier): Order
    {
        return Order::query()
            ->forBusiness($this->tenant->businessOrFail())
            ->where(fn ($query) => $query
                ->where('number', $identifier)
                ->orWhere('reference', $identifier))
            ->with(['currentDelivery.deliveryCompany', 'currentDelivery.rider', 'items'])
            ->orderByDesc('created_at')
            ->firstOrFail();
    }
}
