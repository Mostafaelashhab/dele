<?php

namespace App\Domain\Dispatch;

use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Domain\Deliveries\Events\DeliveryOffersDispatched;
use App\Domain\Deliveries\Events\NoCompanyAvailable;
use App\Domain\Matching\MatchCandidate;
use App\Domain\Matching\MatchingContext;
use App\Domain\Matching\MatchingEngine;
use App\Enums\DeliveryStatus;
use App\Enums\OfferStatus;
use App\Enums\OrderEventType;
use App\Jobs\DispatchDeliveryJob;
use App\Jobs\ExpireDeliveryOfferJob;
use App\Models\BusinessCompanyPreference;
use App\Models\Delivery;
use App\Models\DeliveryOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Runs the delivery request marketplace: rank companies, offer to the best
 * few, and keep widening the net until somebody accepts or the platform runs
 * out of rounds.
 *
 * Offers are made in rounds rather than one company at a time, because a
 * single unresponsive company should not cost a business the whole offer
 * timeout before anyone else is asked.
 */
class DispatchService
{
    public function __construct(
        private readonly MatchingEngine $matchingEngine,
        private readonly DeliveryTransitioner $transitioner,
    ) {}

    /**
     * Run one dispatch round for a delivery.
     *
     * @return Collection<int, DeliveryOffer> the offers created this round
     */
    public function dispatch(Delivery $delivery): Collection
    {
        $delivery->loadMissing(['order.business', 'order.pickupZone', 'order.dropoffZone']);

        if ($delivery->status->isTerminal() || $delivery->status === DeliveryStatus::Accepted) {
            return collect();
        }

        if ($delivery->status !== DeliveryStatus::Searching) {
            $delivery = $this->transitioner->transition(
                $delivery,
                DeliveryStatus::Searching,
                OrderEventType::DeliveryRequested,
                Actor::system('dispatcher'),
            );
        }

        $round = $delivery->dispatch_round + 1;
        $maxRounds = (int) config('platform.dispatch.max_rounds', 4);

        if ($round > $maxRounds) {
            $this->failExhausted($delivery, $round);

            return collect();
        }

        $context = $this->buildContext($delivery);
        $candidates = $this->matchingEngine->rank($context);

        if ($candidates->isEmpty()) {
            $this->handleEmptyRound($delivery, $round, $maxRounds);

            return collect();
        }

        $offers = $this->createOffers($delivery, $candidates, $round);

        $delivery->forceFill([
            'dispatch_round' => $round,
            'offers_sent_count' => $delivery->offers_sent_count + $offers->count(),
        ])->save();

        if ($delivery->status !== DeliveryStatus::Offered) {
            $delivery = $this->transitioner->transition(
                $delivery,
                DeliveryStatus::Offered,
                OrderEventType::DeliveryCompanyOffered,
                Actor::system('dispatcher'),
                [
                    'round' => $round,
                    'companies' => $offers->pluck('delivery_company_id')->all(),
                ],
            );
        }

        DeliveryOffersDispatched::dispatch($delivery, $offers, $round);

        return $offers;
    }

    /**
     * Build the question the matching engine answers, including the business's
     * standing preferences and every company that has already said no.
     */
    public function buildContext(Delivery $delivery): MatchingContext
    {
        $order = $delivery->order;
        $preferences = $order->business->companyPreferences()->get();

        $alreadyOffered = $delivery->offers()
            ->pluck('delivery_company_id')
            ->unique()
            ->values()
            ->all();

        return new MatchingContext(
            order: $order,
            delivery: $delivery,
            business: $order->business,
            pickupPoint: $order->pickupSnapshot()->point(),
            dropoffPoint: $order->dropoffSnapshot()->point(),
            pickupZone: $order->pickupZone,
            dropoffZone: $order->dropoffZone,
            priority: $order->priority,
            packageSize: $order->package_size,
            blockedCompanyIds: $preferences
                ->where('preference', BusinessCompanyPreference::BLOCKED)
                ->pluck('delivery_company_id')
                ->all(),
            preferredCompanyIds: $preferences
                ->where('preference', BusinessCompanyPreference::PREFERRED)
                ->sortByDesc('priority')
                ->pluck('delivery_company_id')
                ->all(),
            excludeCompanyIds: $alreadyOffered,
        );
    }

    /**
     * @param  Collection<int, MatchCandidate>  $candidates
     * @return Collection<int, DeliveryOffer>
     */
    protected function createOffers(Delivery $delivery, Collection $candidates, int $round): Collection
    {
        $fanOut = max(
            (int) config('platform.dispatch.companies_per_round', 2),
            $delivery->order->priority->offerFanOut(),
        );

        $shortlist = $candidates->take($fanOut);

        return DB::transaction(function () use ($delivery, $shortlist, $round): Collection {
            return $shortlist->values()->map(function (MatchCandidate $candidate, int $index) use ($delivery, $round): DeliveryOffer {
                $timeout = $candidate->company->offerTimeoutSeconds();

                $offer = DeliveryOffer::create([
                    'delivery_id' => $delivery->id,
                    'delivery_company_id' => $candidate->company->id,
                    'round' => $round,
                    'rank' => $index + 1,
                    'status' => OfferStatus::Pending,
                    'quoted_price_minor' => $candidate->quote->total,
                    'company_payout_minor' => $candidate->quote->companyPayout,
                    'currency' => $candidate->quote->currency(),
                    'quoted_eta_minutes' => $candidate->estimatedTotalMinutes,
                    'score_bps' => $candidate->scoreBasisPoints(),
                    'score_breakdown' => $candidate->toBreakdown(),
                    'offered_at' => now(),
                    'expires_at' => now()->addSeconds($timeout),
                ]);

                ExpireDeliveryOfferJob::dispatch($offer->id)->delay(now()->addSeconds($timeout + 1));

                return $offer;
            });
        });
    }

    /**
     * Nobody was eligible this round. Try again shortly — riders come online,
     * capacity frees up — and only give up once the rounds are exhausted.
     */
    protected function handleEmptyRound(Delivery $delivery, int $round, int $maxRounds): void
    {
        $delivery->forceFill(['dispatch_round' => $round])->save();

        NoCompanyAvailable::dispatch($delivery, $round);

        $this->transitioner->recordEvent(
            $delivery,
            $delivery->status,
            $delivery->status,
            OrderEventType::NoCompanyAvailable,
            Actor::system('dispatcher'),
            ['round' => $round],
        );

        if ($round >= $maxRounds) {
            $this->failExhausted($delivery, $round);

            return;
        }

        Log::info('Dispatch round found no eligible company; retrying.', [
            'delivery_id' => $delivery->id,
            'round' => $round,
        ]);

        DispatchDeliveryJob::dispatch($delivery->id)
            ->delay(now()->addSeconds((int) config('platform.dispatch.offer_timeout_seconds', 90)));
    }

    protected function failExhausted(Delivery $delivery, int $round): void
    {
        $this->transitioner->transition(
            $delivery,
            DeliveryStatus::Failed,
            OrderEventType::OrderFailed,
            Actor::system('dispatcher'),
            ['reason' => 'no_company_available', 'rounds' => $round],
            ['failure_reason' => 'no_company_available'],
        );
    }
}
