<?php

namespace App\Listeners;

use App\Domain\Deliveries\Events\DeliveryStatusChanged;
use App\Domain\Webhooks\WebhookDispatcher;
use App\Enums\DeliveryStatus;
use App\Enums\WebhookEvent;
use App\Http\Resources\Api\V1\DeliveryResource;
use App\Jobs\RecalculateCompanyMetricsJob;
use App\Jobs\RecordDeliveryFinancialsJob;
use App\Models\Delivery;
use App\Notifications\CustomerDeliveryUpdate;
use App\Notifications\DeliveryAcceptedByCompany;
use App\Notifications\DeliveryProgressed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

/**
 * Every consequence of a delivery moving state hangs off this one listener,
 * which is why no controller or action has to remember to notify anyone.
 *
 * Queued, so a slow webhook fan-out or SMS provider never delays the rider's
 * tap or the dispatcher's click.
 */
class HandleDeliveryStatusChange implements ShouldQueue
{
    public string $queue = 'events';

    public int $tries = 3;

    public function __construct(
        private readonly WebhookDispatcher $webhooks,
    ) {}

    public function handle(DeliveryStatusChanged $event): void
    {
        $delivery = $event->delivery->loadMissing([
            'order.business.users', 'deliveryCompany', 'rider',
        ]);

        $this->notifyBusiness($delivery, $event->to);
        $this->notifyCustomer($delivery, $event->to);
        $this->sendWebhook($delivery, $event->to);
        $this->recordFinancials($delivery, $event->to);
        $this->refreshMetrics($delivery, $event->to);
    }

    protected function notifyBusiness(Delivery $delivery, DeliveryStatus $status): void
    {
        $recipients = $delivery->order->business->users()
            ->wherePivot('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = $status === DeliveryStatus::Accepted
            ? new DeliveryAcceptedByCompany($delivery)
            : new DeliveryProgressed($delivery, $status);

        Notification::send($recipients, $notification);
    }

    /**
     * The recipient never signs in, so they are notified on the phone number
     * the business gave — and only about milestones that concern them.
     */
    protected function notifyCustomer(Delivery $delivery, DeliveryStatus $status): void
    {
        $milestones = [
            DeliveryStatus::PickedUp,
            DeliveryStatus::ArrivedAtDestination,
            DeliveryStatus::Delivered,
            DeliveryStatus::Cancelled,
        ];

        if (! in_array($status, $milestones, true)) {
            return;
        }

        $phone = $delivery->order->dropoffSnapshot()->contactPhone;

        if (blank($phone)) {
            return;
        }

        (new AnonymousNotifiable)
            ->route('sms', $phone)
            ->route('whatsapp', $phone)
            ->notify(new CustomerDeliveryUpdate($delivery, $status));
    }

    protected function sendWebhook(Delivery $delivery, DeliveryStatus $status): void
    {
        $event = WebhookEvent::forDeliveryStatus($status);

        if ($event === null) {
            return;
        }

        $this->webhooks->dispatchForDelivery(
            $event,
            $delivery,
            (new DeliveryResource($delivery))->resolve(),
        );
    }

    /**
     * Money is only posted for a delivery that actually completed. A failed
     * or cancelled delivery produces no charge, so there is nothing to post.
     */
    protected function recordFinancials(Delivery $delivery, DeliveryStatus $status): void
    {
        if ($status !== DeliveryStatus::Delivered) {
            return;
        }

        RecordDeliveryFinancialsJob::dispatch($delivery->id);
    }

    protected function refreshMetrics(Delivery $delivery, DeliveryStatus $status): void
    {
        if (! in_array($status, [DeliveryStatus::Delivered, DeliveryStatus::Failed], true)) {
            return;
        }

        if ($delivery->delivery_company_id === null) {
            return;
        }

        RecalculateCompanyMetricsJob::dispatch($delivery->delivery_company_id)
            ->delay(now()->addSeconds(30));
    }
}
