<?php

namespace Tests\Feature;

use App\Actions\Deliveries\AcceptDeliveryOfferAction;
use App\Actions\Deliveries\AdvanceDeliveryAction;
use App\Actions\Deliveries\AssignRiderAction;
use App\Actions\Deliveries\CancelDeliveryAction;
use App\Actions\Deliveries\RejectDeliveryOfferAction;
use App\Actions\Deliveries\RespondToAssignmentAction;
use App\Actions\Orders\CreateOrderAction;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Domain\Deliveries\Exceptions\InvalidStateTransition;
use App\Domain\Ledger\DeliveryFinancialsRecorder;
use App\Domain\Orders\OrderData;
use App\Domain\Proof\DeliveryConfirmationCode;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tracking\TrackingPresenter;
use App\Enums\AssignmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\EntryType;
use App\Enums\LedgerAccountType;
use App\Enums\OfferStatus;
use App\Enums\OrderEventType;
use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Enums\TransactionCategory;
use App\Jobs\RecordDeliveryFinancialsJob;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Rider;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * The vertical slice, end to end: a business creates a delivery, the network
 * finds a company, a rider carries it, and the money lands in the ledger.
 *
 * Nothing here writes a status directly — every step goes through the same
 * action a controller would call, so a passing test means the real pipeline
 * works, not that a fixture was arranged to look like it did.
 */
class DeliveryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private DeliveryCompany $company;

    private Rider $rider;

    private Zone $pickupZone;

    private Zone $dropoffZone;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->pickupZone = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        $this->dropoffZone = Zone::factory()->at(30.4560, 31.1900)->create(['code' => 'MNS']);

        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);
        PricingRule::factory()->perKilometre(300, freeMeters: 1000)->create(['name' => 'Distance']);

        $this->business = Business::factory()->create();
        $this->company = DeliveryCompany::factory()->create();

        $this->company->serviceAreas()->sync(
            Zone::query()->pluck('id')->mapWithKeys(fn (string $id) => [$id => [
                'accepts_pickup' => true,
                'accepts_dropoff' => true,
                'surcharge_minor' => 0,
            ]])->all()
        );

        $this->rider = Rider::factory()
            ->for($this->company)
            ->online(30.4612, 31.1841)
            ->create();
    }

    #[Test]
    public function a_delivery_travels_from_creation_to_cash_in_the_ledger(): void
    {
        // 1. The business creates the order.
        $order = $this->createOrder();

        $this->assertSame(OrderStatus::Active, $order->fresh()->status);
        $this->assertNotNull($order->currentDelivery);

        $delivery = $order->currentDelivery;

        $this->assertGreaterThan(0, $delivery->price()->minor);
        $this->assertNotEmpty($delivery->tracking_token);
        $this->assertStringStartsWith('del_', $delivery->public_id);

        // 2. Dispatch ran inline on the sync queue and offered the delivery.
        $delivery->refresh();

        $this->assertSame(DeliveryStatus::Offered, $delivery->status);

        $offer = $delivery->offers()->where('delivery_company_id', $this->company->id)->firstOrFail();

        $this->assertSame(OfferStatus::Pending, $offer->status);
        $this->assertGreaterThan(0, $offer->quotedPrice()->minor);

        // 3. The company accepts; the offer's quote becomes the binding price.
        $delivery = app(AcceptDeliveryOfferAction::class)->handle($offer->fresh());

        $this->assertSame(DeliveryStatus::Accepted, $delivery->status);
        $this->assertSame($this->company->id, $delivery->delivery_company_id);
        $this->assertSame($offer->quotedPrice()->minor, $delivery->price()->minor);

        // 4. A dispatcher offers the job to a rider.
        $assignment = app(AssignRiderAction::class)->handle($delivery, $this->rider);

        $this->assertSame(AssignmentStatus::Offered, $assignment->status);

        // The delivery is still the company's problem until the rider answers.
        $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);

        // 5. The rider accepts, which consumes a slot of their capacity.
        $delivery = app(RespondToAssignmentAction::class)->accept($assignment);

        $this->assertSame(DeliveryStatus::Assigned, $delivery->status);
        $this->assertSame($this->rider->id, $delivery->rider_id);
        $this->assertSame(1, $this->rider->fresh()->active_deliveries_count);

        // 6. The rider works through the journey.
        $advance = app(AdvanceDeliveryAction::class);

        $delivery = $advance->arrivedAtPickup($delivery, $this->rider);
        $this->assertSame(DeliveryStatus::ArrivedAtPickup, $delivery->status);

        $delivery = $advance->pickedUp($delivery, $this->rider);
        $this->assertSame(DeliveryStatus::PickedUp, $delivery->status);
        $this->assertNotNull($delivery->picked_up_at);

        $delivery = $advance->startTransit($delivery, $this->rider);
        $delivery = $advance->arrivedAtDestination($delivery, $this->rider);

        // The recipient reads their code to the rider, who types it in. A
        // delivery cannot close without this or a photograph.
        $this->assertTrue(
            app(DeliveryConfirmationCode::class)
                ->verify($delivery, $delivery->confirmation_code)
                ->isVerified()
        );

        $delivery = $advance->delivered(
            delivery: $delivery->fresh(),
            rider: $this->rider,
            receivedBy: 'سارة محمود',
        );

        // 7. Delivered, with the rider's capacity released.
        $this->assertSame(DeliveryStatus::Delivered, $delivery->status);
        $this->assertSame('سارة محمود', $delivery->received_by);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertSame(0, $this->rider->fresh()->active_deliveries_count);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);

        // 8. The money is in the ledger, and it balances.
        $entries = FinancialTransaction::query()->where('delivery_id', $delivery->id)->get();

        $this->assertTrue($entries->isNotEmpty(), 'No ledger entries were posted.');

        $net = $entries->sum(fn (FinancialTransaction $entry) => $entry->entry_type === EntryType::Credit
            ? $entry->amount()->minor
            : -$entry->amount()->minor);

        $this->assertSame(0, $net, 'The ledger entries for this delivery do not balance.');

        // The business is charged the full price; the platform keeps its fee.
        $charge = $entries->firstWhere('category', TransactionCategory::BusinessCharge);
        $this->assertSame($delivery->price()->minor, $charge->amount()->minor);
        $this->assertSame(EntryType::Debit, $charge->entry_type);

        $fee = $entries->firstWhere('category', TransactionCategory::PlatformFee);
        $this->assertSame(LedgerAccountType::Platform, $fee->account_type);
        $this->assertSame(EntryType::Credit, $fee->entry_type);
    }

    #[Test]
    public function every_step_leaves_a_customer_readable_timeline(): void
    {
        $delivery = $this->deliverFully();

        $timeline = $delivery->order
            ->events()
            ->customerVisible()
            ->chronological()
            ->pluck('type')
            ->all();

        foreach ([
            OrderEventType::OrderCreated,
            OrderEventType::DeliveryAccepted,
            OrderEventType::RiderAssigned,
            OrderEventType::OrderPickedUp,
            OrderEventType::DeliveryStarted,
            OrderEventType::OrderDelivered,
        ] as $expected) {
            $this->assertContains($expected, $timeline, "Timeline is missing {$expected->value}.");
        }
    }

    #[Test]
    public function accepting_an_offer_withdraws_the_offers_made_to_everyone_else(): void
    {
        $rival = DeliveryCompany::factory()->create();
        Rider::factory()->for($rival)->online()->create();

        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        $this->assertGreaterThan(1, $delivery->offers()->count());

        $winner = $delivery->offers()->orderBy('rank')->first();

        app(AcceptDeliveryOfferAction::class)->handle($winner);

        $others = $delivery->offers()->whereKeyNot($winner->id)->get();

        foreach ($others as $offer) {
            $this->assertSame(
                OfferStatus::Withdrawn,
                $offer->status,
                'A losing offer was left open after another company accepted.',
            );
        }
    }

    #[Test]
    public function a_second_company_cannot_accept_a_delivery_that_is_already_taken(): void
    {
        $rival = DeliveryCompany::factory()->create();
        Rider::factory()->for($rival)->online()->create();

        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        $offers = $delivery->offers()->orderBy('rank')->get();

        app(AcceptDeliveryOfferAction::class)->handle($offers->first());

        $this->expectException(RuntimeException::class);

        // The second dispatcher was looking at a stale inbox.
        app(AcceptDeliveryOfferAction::class)->handle($offers->last()->fresh());
    }

    #[Test]
    public function rejecting_the_last_open_offer_sends_the_delivery_back_out(): void
    {
        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        foreach ($delivery->offers()->pending()->get() as $offer) {
            app(RejectDeliveryOfferAction::class)->handle($offer, null, 'too_busy');
        }

        $delivery->refresh();

        // Still live, still unassigned, and the dispatcher has been asked to
        // widen the net rather than the order simply failing.
        $this->assertNull($delivery->delivery_company_id);
        $this->assertFalse($delivery->status->isTerminal());
        $this->assertGreaterThanOrEqual(1, $delivery->dispatch_round);
    }

    #[Test]
    public function a_rider_declining_is_not_reported_to_the_customer_as_an_assignment(): void
    {
        $order = $this->createOrder();

        $delivery = app(AcceptDeliveryOfferAction::class)
            ->handle($order->currentDelivery->refresh()->offers()->pending()->firstOrFail());

        // The first rider is offered the job and turns it down.
        $declining = Rider::factory()->for($this->company)->online()->create();

        app(RespondToAssignmentAction::class)->reject(
            app(AssignRiderAction::class)->handle($delivery, $declining),
            'busy',
        );

        // A second rider takes it.
        $delivery = app(RespondToAssignmentAction::class)->accept(
            app(AssignRiderAction::class)->handle($delivery->fresh(), $this->rider),
        );

        $this->assertSame($this->rider->id, $delivery->rider_id);

        // The decline is on the record for the operator...
        $this->assertDatabaseHas('order_events', [
            'delivery_id' => $delivery->id,
            'type' => OrderEventType::RiderDeclined->value,
            'is_customer_visible' => false,
        ]);

        // ...but the customer is told a rider was assigned exactly once, and
        // only for the rider who actually took the job. Recording the decline
        // as an assignment put a second, untrue entry on their timeline.
        $timeline = app(TrackingPresenter::class)->present($delivery)['timeline'];
        $types = array_column($timeline, 'type');

        $this->assertSame([OrderEventType::RiderAssigned->value], array_values(array_filter(
            $types,
            fn (string $type): bool => in_array($type, [
                OrderEventType::RiderAssigned->value,
                OrderEventType::RiderDeclined->value,
            ], true),
        )));
    }

    #[Test]
    public function an_expired_offer_is_closed_and_counts_against_the_company(): void
    {
        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        $offer = $delivery->offers()->pending()->firstOrFail();

        $this->travel(200)->seconds();

        app(RejectDeliveryOfferAction::class)->handle($offer->fresh(), null, 'timeout', expired: true);

        $this->assertSame(OfferStatus::Expired, $offer->fresh()->status);

        $this->travelBack();
    }

    #[Test]
    public function cash_on_delivery_records_the_collected_amount_as_owed_to_the_business(): void
    {
        $delivery = $this->deliverFully(
            paymentType: PaymentType::CashOnDelivery,
            codAmount: Money::ofMajor('250.00'),
        );

        $this->assertSame(25000, $delivery->cod_collected_minor->minor);

        $cod = FinancialTransaction::query()
            ->where('delivery_id', $delivery->id)
            ->where('category', TransactionCategory::CodCollected)
            ->get();

        $this->assertCount(2, $cod, 'Cash on delivery must post a balanced pair.');

        $owedToBusiness = $cod->firstWhere('account_type', LedgerAccountType::Business);

        $this->assertSame(EntryType::Credit, $owedToBusiness->entry_type);
        $this->assertSame(25000, $owedToBusiness->amount()->minor);
    }

    #[Test]
    public function financials_are_posted_once_however_many_times_the_job_runs(): void
    {
        $delivery = $this->deliverFully();

        $before = FinancialTransaction::query()->where('delivery_id', $delivery->id)->count();

        // A retried queue job, or a duplicated event, must not bill twice.
        app(RecordDeliveryFinancialsJob::class, ['deliveryId' => $delivery->id])
            ->handle(
                app(DeliveryFinancialsRecorder::class),
                app(DeliveryTransitioner::class),
            );

        $this->assertSame(
            $before,
            FinancialTransaction::query()->where('delivery_id', $delivery->id)->count(),
        );
    }

    #[Test]
    public function a_business_can_cancel_before_pickup_but_not_after(): void
    {
        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        $offer = $delivery->offers()->pending()->firstOrFail();
        $delivery = app(AcceptDeliveryOfferAction::class)->handle($offer);

        $assignment = app(AssignRiderAction::class)->handle($delivery, $this->rider);
        $delivery = app(RespondToAssignmentAction::class)->accept($assignment);

        // Cancelling now is legitimate, and must free the rider's slot.
        $cancelled = app(CancelDeliveryAction::class)->handle($delivery, 'customer_changed_mind');

        $this->assertSame(DeliveryStatus::Cancelled, $cancelled->status);
        $this->assertSame(0, $this->rider->fresh()->active_deliveries_count);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    #[Test]
    public function a_picked_up_parcel_can_no_longer_be_cancelled(): void
    {
        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        $delivery = app(AcceptDeliveryOfferAction::class)
            ->handle($delivery->offers()->pending()->firstOrFail());

        $assignment = app(AssignRiderAction::class)->handle($delivery, $this->rider);
        $delivery = app(RespondToAssignmentAction::class)->accept($assignment);

        $advance = app(AdvanceDeliveryAction::class);
        $delivery = $advance->arrivedAtPickup($delivery, $this->rider);
        $delivery = $advance->pickedUp($delivery, $this->rider);

        // The parcel is in a rider's hands; the state machine must refuse.
        $this->expectException(InvalidStateTransition::class);

        app(CancelDeliveryAction::class)->handle($delivery, 'too_late');
    }

    #[Test]
    public function a_rider_cannot_advance_a_delivery_that_is_not_theirs(): void
    {
        $delivery = $this->acceptedAndAssigned();
        $stranger = Rider::factory()->for($this->company)->online()->create();

        $this->expectException(RuntimeException::class);

        app(AdvanceDeliveryAction::class)->arrivedAtPickup($delivery, $stranger);
    }

    #[Test]
    public function a_rider_from_another_company_cannot_be_assigned(): void
    {
        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        $delivery = app(AcceptDeliveryOfferAction::class)
            ->handle($delivery->offers()->pending()->firstOrFail());

        $outsider = Rider::factory()->online()->create();

        $this->expectException(RuntimeException::class);

        app(AssignRiderAction::class)->handle($delivery, $outsider);
    }

    #[Test]
    public function a_failed_delivery_posts_no_charge(): void
    {
        $delivery = $this->acceptedAndAssigned();

        $advance = app(AdvanceDeliveryAction::class);
        $delivery = $advance->arrivedAtPickup($delivery, $this->rider);
        $delivery = $advance->pickedUp($delivery, $this->rider);
        $delivery = $advance->startTransit($delivery, $this->rider);

        $delivery = $advance->failed($delivery, $this->rider, 'customer_unreachable');

        $this->assertSame(DeliveryStatus::Failed, $delivery->status);
        $this->assertSame('customer_unreachable', $delivery->failure_reason);
        $this->assertSame(0, $this->rider->fresh()->active_deliveries_count);

        // Nobody pays for a delivery that did not happen.
        $this->assertSame(
            0,
            FinancialTransaction::query()->where('delivery_id', $delivery->id)->count(),
        );
    }

    private function createOrder(
        PaymentType $paymentType = PaymentType::Prepaid,
        ?Money $codAmount = null,
    ): Order {
        return app(CreateOrderAction::class)->handle(
            business: $this->business,
            data: new OrderData(
                pickup: new LocationSnapshot(
                    contactName: 'المتجر',
                    contactPhone: '01000000001',
                    addressLine: 'وسط البلد',
                    area: $this->pickupZone->name,
                    city: 'Banha',
                    latitude: 30.4610,
                    longitude: 31.1840,
                    zoneId: $this->pickupZone->id,
                ),
                dropoff: new LocationSnapshot(
                    contactName: 'سارة محمود',
                    contactPhone: '01000000002',
                    addressLine: '١٢ شارع الجلاء',
                    area: $this->dropoffZone->name,
                    city: 'Banha',
                    latitude: 30.4560,
                    longitude: 31.1900,
                    zoneId: $this->dropoffZone->id,
                ),
                paymentType: $paymentType,
                codAmount: $codAmount,
                reference: 'TEST-'.fake()->unique()->numerify('####'),
            ),
        );
    }

    private function acceptedAndAssigned(): Delivery
    {
        $order = $this->createOrder();
        $delivery = $order->currentDelivery->refresh();

        $delivery = app(AcceptDeliveryOfferAction::class)
            ->handle($delivery->offers()->pending()->firstOrFail());

        $assignment = app(AssignRiderAction::class)->handle($delivery, $this->rider);

        return app(RespondToAssignmentAction::class)->accept($assignment);
    }

    private function deliverFully(
        PaymentType $paymentType = PaymentType::Prepaid,
        ?Money $codAmount = null,
    ): Delivery {
        $order = $this->createOrder($paymentType, $codAmount);
        $delivery = $order->currentDelivery->refresh();

        $delivery = app(AcceptDeliveryOfferAction::class)
            ->handle($delivery->offers()->pending()->firstOrFail());

        $assignment = app(AssignRiderAction::class)->handle($delivery, $this->rider);
        $delivery = app(RespondToAssignmentAction::class)->accept($assignment);

        $advance = app(AdvanceDeliveryAction::class);
        $delivery = $advance->arrivedAtPickup($delivery, $this->rider);
        $delivery = $advance->pickedUp($delivery, $this->rider);
        $delivery = $advance->startTransit($delivery, $this->rider);
        $delivery = $advance->arrivedAtDestination($delivery, $this->rider);

        app(DeliveryConfirmationCode::class)->verify($delivery, $delivery->confirmation_code);

        return $advance->delivered(
            delivery: $delivery->fresh(),
            rider: $this->rider,
            receivedBy: 'سارة محمود',
            codCollected: $codAmount,
        );
    }
}
