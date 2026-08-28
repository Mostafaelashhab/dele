<?php

namespace App\Actions\Deliveries;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Enums\AuditAction;
use App\Enums\DeliveryStatus;
use App\Enums\OfferStatus;
use App\Enums\OrderEventType;
use App\Models\Delivery;
use App\Models\DeliveryOffer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A delivery company claims an offer.
 *
 * Two companies can be looking at the same offer at the same second, so the
 * claim is decided by a conditional update inside a transaction: the first
 * writer wins and every later one is told the work is gone.
 */
class AcceptDeliveryOfferAction
{
    public function __construct(
        private readonly DeliveryTransitioner $transitioner,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(DeliveryOffer $offer, ?User $respondedBy = null): Delivery
    {
        $offer = DB::transaction(function () use ($offer, $respondedBy): DeliveryOffer {
            /** @var DeliveryOffer $locked */
            $locked = DeliveryOffer::query()
                ->whereKey($offer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isAnswerable()) {
                throw new RuntimeException(__('delivery.errors.offer_not_answerable'));
            }

            /** @var Delivery $delivery */
            $delivery = Delivery::query()
                ->whereKey($locked->delivery_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Somebody else already took it between the inbox rendering and
            // this click.
            if ($delivery->delivery_company_id !== null) {
                throw new RuntimeException(__('delivery.errors.already_assigned'));
            }

            $locked->forceFill([
                'status' => OfferStatus::Accepted,
                'responded_at' => now(),
                'responded_by_user_id' => $respondedBy?->id,
            ])->save();

            // Everything else on the table is withdrawn in the same
            // transaction, so no second company can accept afterwards.
            DeliveryOffer::query()
                ->where('delivery_id', $delivery->id)
                ->whereKeyNot($locked->id)
                ->where('status', OfferStatus::Pending)
                ->update([
                    'status' => OfferStatus::Withdrawn->value,
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);

            return $locked;
        });

        $offer->loadMissing(['delivery', 'deliveryCompany']);
        $company = $offer->deliveryCompany;

        // The accepted offer's quote is the binding price, replacing the
        // indicative one shown at order time.
        $delivery = $this->transitioner->transition(
            $offer->delivery,
            DeliveryStatus::Accepted,
            OrderEventType::DeliveryAccepted,
            $respondedBy ? Actor::user($respondedBy) : Actor::company($company),
            [
                'delivery_company_id' => $company->id,
                'delivery_company_name' => $company->name,
                'price_minor' => $offer->quotedPrice()->minor,
                'eta_minutes' => $offer->quoted_eta_minutes,
            ],
            [
                'delivery_company_id' => $company->id,
                'price_minor' => $offer->quotedPrice()->minor,
                'company_payout_minor' => $offer->payout()->minor,
                'platform_fee_minor' => $offer->quotedPrice()->minus($offer->payout())->minor,
                'estimated_minutes' => $offer->quoted_eta_minutes,
                'estimated_delivery_at' => now()->addMinutes($offer->quoted_eta_minutes),
            ],
        );

        $this->auditLogger->log(
            action: AuditAction::OfferAccepted,
            entity: $delivery,
            actor: $respondedBy ? Actor::user($respondedBy) : Actor::company($company),
            description: __('audit.description.offer_accepted', [
                'company' => $company->name,
                'order' => $delivery->order->number,
            ]),
            context: ['offer_id' => $offer->id, 'round' => $offer->round],
            tenantType: 'delivery_company',
            tenantId: $company->id,
        );

        return $delivery;
    }
}
