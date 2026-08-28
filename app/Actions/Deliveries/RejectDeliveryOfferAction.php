<?php

namespace App\Actions\Deliveries;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Domain\Deliveries\Events\DeliveryOfferRejected;
use App\Enums\AuditAction;
use App\Enums\OfferStatus;
use App\Enums\OrderEventType;
use App\Models\DeliveryOffer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A company declines an offer, or lets it lapse.
 *
 * Both paths are the same operation with a different reason, and both feed
 * the acceptance-rate metric that later shapes how often that company is
 * asked again.
 */
class RejectDeliveryOfferAction
{
    public function __construct(
        private readonly DeliveryTransitioner $transitioner,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        DeliveryOffer $offer,
        ?User $respondedBy = null,
        ?string $reason = null,
        bool $expired = false,
    ): DeliveryOffer {
        $offer = DB::transaction(function () use ($offer, $respondedBy, $reason, $expired): ?DeliveryOffer {
            /** @var DeliveryOffer $locked */
            $locked = DeliveryOffer::query()
                ->whereKey($offer->id)
                ->lockForUpdate()
                ->firstOrFail();

            // An offer already answered — accepted, withdrawn, or expired by a
            // racing sweep — is left exactly as it is.
            if ($locked->status !== OfferStatus::Pending) {
                return null;
            }

            $locked->forceFill([
                'status' => $expired ? OfferStatus::Expired : OfferStatus::Rejected,
                'responded_at' => now(),
                'responded_by_user_id' => $respondedBy?->id,
                'rejection_reason' => $reason,
            ])->save();

            return $locked;
        }) ?? $offer->fresh();

        if ($offer->status === OfferStatus::Pending) {
            return $offer;
        }

        $offer->loadMissing(['delivery', 'deliveryCompany']);

        $actor = $expired
            ? Actor::system('offer_expiry')
            : ($respondedBy ? Actor::user($respondedBy) : Actor::company($offer->deliveryCompany));

        $this->transitioner->recordEvent(
            $offer->delivery,
            $offer->delivery->status,
            $offer->delivery->status,
            $expired ? OrderEventType::DeliveryOfferExpired : OrderEventType::DeliveryOfferRejected,
            $actor,
            [
                'offer_id' => $offer->id,
                'delivery_company_id' => $offer->delivery_company_id,
                'delivery_company_name' => $offer->deliveryCompany->name,
                'reason' => $reason,
                'round' => $offer->round,
            ],
        );

        if (! $expired) {
            $this->auditLogger->log(
                action: AuditAction::OfferRejected,
                entity: $offer->delivery,
                actor: $actor,
                description: __('audit.description.offer_rejected', [
                    'company' => $offer->deliveryCompany->name,
                ]),
                context: ['offer_id' => $offer->id, 'reason' => $reason],
                tenantType: 'delivery_company',
                tenantId: $offer->delivery_company_id,
            );
        }

        DeliveryOfferRejected::dispatch($offer, $actor, $expired);

        return $offer;
    }
}
