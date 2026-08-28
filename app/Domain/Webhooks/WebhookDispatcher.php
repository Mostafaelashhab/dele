<?php

namespace App\Domain\Webhooks;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEvent;
use App\Jobs\SendWebhookJob;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Fans a domain event out to every endpoint subscribed to it.
 *
 * Each (endpoint, event_id) pair is unique in the database, so a retried
 * listener or a duplicated domain event produces one webhook delivery rather
 * than two identical calls to a customer's server.
 */
class WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, WebhookDelivery>
     */
    public function dispatch(
        WebhookEvent $event,
        Model $owner,
        array $payload,
        ?string $eventId = null,
    ): Collection {
        $eventId ??= 'evt_'.Str::lower((string) Str::ulid());

        return $this->endpointsFor($owner, $event)
            ->map(fn (WebhookEndpoint $endpoint) => $this->queue($endpoint, $event, $payload, $eventId))
            ->filter()
            ->values();
    }

    /**
     * Notify both sides of a delivery: the business that placed it and the
     * company carrying it. Each only ever receives its own tenant's data.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatchForDelivery(WebhookEvent $event, Delivery $delivery, array $payload): void
    {
        $eventId = 'evt_'.Str::lower((string) Str::ulid());

        $delivery->loadMissing(['business', 'deliveryCompany']);

        if ($delivery->business instanceof Business) {
            $this->dispatch($event, $delivery->business, $payload, $eventId);
        }

        if ($delivery->deliveryCompany instanceof DeliveryCompany) {
            $this->dispatch($event, $delivery->deliveryCompany, $payload, $eventId.'_c');
        }
    }

    /**
     * @return Collection<int, WebhookEndpoint>
     */
    protected function endpointsFor(Model $owner, WebhookEvent $event): Collection
    {
        return WebhookEndpoint::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('is_active', true)
            ->whereNull('disabled_at')
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint) => $endpoint->subscribesTo($event));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function queue(
        WebhookEndpoint $endpoint,
        WebhookEvent $event,
        array $payload,
        string $eventId,
    ): ?WebhookDelivery {
        try {
            $delivery = WebhookDelivery::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event' => $event,
                'event_id' => $eventId,
                'payload' => [
                    'id' => $eventId,
                    'type' => $event->value,
                    'created_at' => now()->toIso8601String(),
                    'data' => $payload,
                ],
                'status' => WebhookDeliveryStatus::Pending,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already queued by an earlier run of the same event.
            return null;
        }

        SendWebhookJob::dispatch($delivery->id);

        return $delivery;
    }
}
