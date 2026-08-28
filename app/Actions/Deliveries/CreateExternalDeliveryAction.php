<?php

namespace App\Actions\Deliveries;

use App\Actions\Orders\CreateOrderAction;
use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Domain\Orders\OrderData;
use App\Enums\AccountStatus;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A job the company already had, entered so the customer gets a tracking link.
 *
 * The company found this work itself — a phone call, a walk-in, a regular
 * customer — so there is nothing to dispatch: the carrier is decided before
 * the order exists. It skips matching entirely and is marked `is_external` so
 * nothing downstream has to infer that from an absent dispatch round.
 *
 * Everything after the hand-off is identical to a dispatched delivery: the
 * same states, the same tracking page, the same handover code, the same proof
 * requirement. That sameness is the point — a customer cannot tell, and should
 * not have to, whether their parcel arrived through the network or beside it.
 *
 * It moves through real transitions rather than having its status written
 * directly, so the timeline a customer reads is the same shape either way.
 *
 * The chosen rider is *offered* the job rather than having it forced on them,
 * for the same reason: a rider confirms they have a parcel by accepting it,
 * and an external job that skipped that step would be the one delivery in the
 * system whose rider never said yes. If they do not accept, the assignment
 * expires and the company picks someone else — exactly as it would otherwise.
 */
class CreateExternalDeliveryAction
{
    public function __construct(
        private readonly CreateOrderAction $createOrder,
        private readonly AssignRiderAction $assignRider,
        private readonly DeliveryTransitioner $transitioner,
    ) {}

    public function handle(
        DeliveryCompany $company,
        OrderData $data,
        Rider $rider,
        ?User $creator = null,
    ): Delivery {
        if ($rider->delivery_company_id !== $company->id) {
            throw new RuntimeException(__('delivery.errors.rider_wrong_company'));
        }

        return DB::transaction(function () use ($company, $data, $rider, $creator): Delivery {
            $order = $this->createOrder->handle(
                business: $this->senderFor($company),
                data: $data,
                creator: $creator,
                // The whole point: this never goes to the dispatcher.
                dispatchImmediately: false,
            );

            $delivery = $order->currentDelivery;

            $delivery->forceFill([
                'is_external' => true,
                'delivery_company_id' => $company->id,
            ])->save();

            $actor = $creator ? Actor::user($creator) : Actor::system();

            // Pending → Searching → Accepted, walked properly rather than
            // written, so the customer's timeline reads the same as any other.
            $delivery = $this->transitioner->transition(
                $delivery, DeliveryStatus::Searching, OrderEventType::DeliveryRequested, $actor
            );

            $delivery = $this->transitioner->transition(
                $delivery,
                DeliveryStatus::Accepted,
                OrderEventType::DeliveryAccepted,
                $actor,
                ['external' => true, 'delivery_company_name' => $company->displayName()],
            );

            $this->assignRider->handle($delivery->fresh(), $rider, $creator);

            return $delivery->fresh();
        });
    }

    /**
     * The sender a company's own jobs are recorded against.
     *
     * Every order needs a business, and an external job has none — the sender
     * is whoever walked in. Rather than making the column nullable and forcing
     * every screen to handle an order from nobody, each company gets one
     * bookkeeping record for the work it brought itself. It has no login and
     * no portal; it exists so these deliveries have somewhere to hang.
     */
    private function senderFor(DeliveryCompany $company): Business
    {
        $slug = 'external-'.$company->slug;

        return Business::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $company->name,
                'name_ar' => $company->displayName(),
                'category' => 'other',
                'contact_person' => $company->contact_person,
                'phone' => $company->phone,
                'email' => $company->email,
                'status' => AccountStatus::Active,
                'is_individual' => true,
            ]
        );
    }

    /**
     * A stable reference for an external job, so a company can find it again
     * against whatever it writes on its own paperwork.
     */
    public static function reference(): string
    {
        return 'EXT-'.Str::upper(Str::random(6));
    }
}
