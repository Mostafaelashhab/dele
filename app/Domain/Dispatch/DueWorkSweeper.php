<?php

namespace App\Domain\Dispatch;

use App\Actions\Deliveries\RejectDeliveryOfferAction;
use App\Actions\Deliveries\RespondToAssignmentAction;
use App\Enums\AssignmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\OfferStatus;
use App\Jobs\DispatchDeliveryJob;
use App\Jobs\SendWebhookJob;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryOffer;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Everything that becomes due with the passing of time, done on read.
 *
 * A timeout needs something to happen later, and this network runs without a
 * scheduler: there is no cron, no worker, nothing ticking. So instead of a
 * timer, the work is swept whenever somebody looks at the system — opening the
 * offer inbox, loading the operations board, placing an order.
 *
 * The trade this makes is worth stating plainly: nothing moves while nobody is
 * looking. An offer that timed out during a quiet hour rolls over on the next
 * request rather than at the instant it expired. For a network whose operators
 * sit on these screens all day that is a fair trade, and it is strictly better
 * than the alternative it replaces, where a missing cron entry meant nothing
 * moved at all.
 *
 * Every branch is idempotent and re-checks its own deadline, so running this
 * twice, or alongside a scheduler if one is ever added, changes nothing.
 */
class DueWorkSweeper
{
    /**
     * How often the sweep may actually run, regardless of traffic.
     *
     * Without this a busy board would re-sweep on every poll of every open
     * tab. The lock is what keeps "on read" from meaning "on every read".
     */
    private const THROTTLE_SECONDS = 10;

    /**
     * Sweep, unless another request swept a moment ago.
     *
     * @return array<string, int> what was actioned, for callers that report it
     */
    public function sweep(bool $force = false): array
    {
        if (! $force && ! Cache::add('dispatch:sweep', true, self::THROTTLE_SECONDS)) {
            return [];
        }

        return [
            'offers_expired' => $this->expireOffers(),
            'assignments_expired' => $this->expireAssignments(),
            'deliveries_redispatched' => $this->redispatchStalled(),
            'webhooks_retried' => $this->retryWebhooks(),
        ];
    }

    /**
     * Offers nobody answered before their deadline.
     */
    private function expireOffers(): int
    {
        $due = DeliveryOffer::query()
            ->where('status', OfferStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['delivery', 'deliveryCompany'])
            ->limit(50)
            ->get();

        $action = app(RejectDeliveryOfferAction::class);
        $count = 0;

        foreach ($due as $offer) {
            // Rejecting an offer can trigger the next dispatch round, which is
            // the point — but one bad row must not stop the sweep.
            $this->attempt(function () use ($action, $offer, &$count): void {
                $action->handle($offer, null, 'timeout', expired: true);
                $count++;
            });
        }

        return $count;
    }

    /**
     * Riders who never answered an assignment.
     */
    private function expireAssignments(): int
    {
        $due = DeliveryAssignment::query()
            ->where('status', AssignmentStatus::Offered)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['delivery', 'rider'])
            ->limit(50)
            ->get();

        $action = app(RespondToAssignmentAction::class);
        $count = 0;

        foreach ($due as $assignment) {
            $this->attempt(function () use ($action, $assignment, &$count): void {
                $action->reject($assignment, 'timeout', expired: true);
                $count++;
            });
        }

        return $count;
    }

    /**
     * Deliveries left with no company and no open offer.
     *
     * The safety net beneath the other two: whatever the reason a delivery
     * stopped moving, this puts it back into dispatch.
     */
    private function redispatchStalled(): int
    {
        $stalled = Delivery::query()
            ->whereIn('status', [
                DeliveryStatus::Pending->value,
                DeliveryStatus::Searching->value,
                DeliveryStatus::Offered->value,
            ])
            ->whereNull('delivery_company_id')
            ->where('updated_at', '<=', now()->subMinutes(
                (int) config('platform.dispatch.stalled_after_minutes', 5)
            ))
            ->whereDoesntHave('offers', fn ($query) => $query->open())
            ->limit(25)
            ->get(['id']);

        foreach ($stalled as $delivery) {
            $this->attempt(fn () => DispatchDeliveryJob::dispatch($delivery->id));
        }

        return $stalled->count();
    }

    private function retryWebhooks(): int
    {
        $due = WebhookDelivery::query()
            ->due()
            ->whereHas('webhookEndpoint', fn ($query) => $query
                ->where('is_active', true)
                ->whereNull('disabled_at'))
            ->orderBy('next_attempt_at')
            ->limit(25)
            ->get(['id']);

        foreach ($due as $delivery) {
            $this->attempt(fn () => SendWebhookJob::dispatch($delivery->id));
        }

        return $due->count();
    }

    /**
     * Run one unit of sweep work without letting it take the request down.
     *
     * This runs inside somebody's page load. A delivery that cannot be
     * expired is a problem worth logging, but it is not a reason to show an
     * operator an error page for a delivery they were not even looking at.
     */
    private function attempt(callable $work): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
