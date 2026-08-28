<?php

namespace Tests\Feature;

use App\Actions\Deliveries\AdvanceDeliveryAction;
use App\Actions\Deliveries\CreateExternalDeliveryAction;
use App\Actions\Deliveries\RespondToAssignmentAction;
use App\Domain\Orders\OrderData;
use App\Domain\Proof\DeliveryConfirmationCode;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Enums\AssignmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\PaymentType;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryCompany;
use App\Models\DeliveryOffer;
use App\Models\PricingRule;
use App\Models\Rider;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Work a company brought itself, entered for the tracking.
 *
 * The requirement is that a customer cannot tell the difference: the same
 * tracking page, the same handover code, the same proof rule. The one thing
 * that must differ is that it never touches the dispatcher.
 */
class ExternalDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryCompany $company;

    private Rider $rider;

    private Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->zone = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);

        $this->company = DeliveryCompany::factory()->create(['working_hours' => null]);
        $this->rider = Rider::factory()->for($this->company)->online(30.4612, 31.1841)->create();
    }

    #[Test]
    public function a_company_can_enter_a_job_it_already_had(): void
    {
        $delivery = $this->create();

        $this->assertTrue($delivery->is_external);
        $this->assertSame($this->company->id, $delivery->delivery_company_id);
        $this->assertSame(DeliveryStatus::Accepted, $delivery->status);

        // The rider is offered the job rather than having it forced on them,
        // so they confirm they have the parcel the same way they always do.
        $assignment = DeliveryAssignment::where('delivery_id', $delivery->id)->sole();

        $this->assertSame($this->rider->id, $assignment->rider_id);
        $this->assertSame(AssignmentStatus::Offered, $assignment->status);
    }

    /**
     * The one thing that must be different.
     */
    #[Test]
    public function it_never_goes_through_the_dispatcher(): void
    {
        $delivery = $this->create();

        $this->assertSame(0, DeliveryOffer::where('delivery_id', $delivery->id)->count());
        $this->assertSame(0, (int) $delivery->offers_sent_count);
        $this->assertSame(0, (int) $delivery->dispatch_round);
    }

    /**
     * The things that must be identical.
     */
    #[Test]
    public function the_customer_gets_the_same_tracking_and_the_same_proof_rule(): void
    {
        $delivery = $this->create();

        $this->assertNotNull($delivery->tracking_token);
        $this->assertNotNull($delivery->confirmation_code);

        $this->get(route('tracking.show', $delivery->tracking_token))->assertOk();

        // The rider confirms they have the parcel exactly as they would on a
        // dispatched job — an external delivery is offered, not forced.
        $assignment = DeliveryAssignment::where('delivery_id', $delivery->id)->sole();
        $delivery = app(RespondToAssignmentAction::class)->accept($assignment);

        $this->assertSame($this->rider->id, $delivery->rider_id);

        // And it still cannot be closed without evidence.
        $advance = app(AdvanceDeliveryAction::class);

        $delivery = $advance->arrivedAtPickup($delivery, $this->rider);
        $delivery = $advance->pickedUp($delivery, $this->rider);
        $delivery = $advance->startTransit($delivery, $this->rider);
        $delivery = $advance->arrivedAtDestination($delivery, $this->rider);

        try {
            $advance->delivered(delivery: $delivery, rider: $this->rider);
            $this->fail('An external delivery must still require proof.');
        } catch (RuntimeException $e) {
            $this->assertSame(__('rider.proof.required'), $e->getMessage());
        }

        app(DeliveryConfirmationCode::class)->verify($delivery, $delivery->confirmation_code);

        $closed = $advance->delivered(delivery: $delivery->fresh(), rider: $this->rider);

        $this->assertSame(DeliveryStatus::Delivered, $closed->status);
        $this->assertTrue($closed->hasProofOfDelivery());
    }

    #[Test]
    public function the_timeline_reads_like_any_other_delivery(): void
    {
        $delivery = $this->create();

        $types = $delivery->order->events()->pluck('type')->map(fn ($t) => $t->value)->all();

        // Walked through real transitions, not written straight to Assigned.
        $this->assertContains('DeliveryRequested', $types);
        $this->assertContains('DeliveryAccepted', $types);
    }

    #[Test]
    public function a_rider_from_another_company_is_refused(): void
    {
        $stranger = Rider::factory()->for(DeliveryCompany::factory()->create())->online()->create();

        $this->expectException(RuntimeException::class);

        app(CreateExternalDeliveryAction::class)->handle(
            company: $this->company,
            data: $this->orderData(),
            rider: $stranger,
        );
    }

    private function create(): Delivery
    {
        return app(CreateExternalDeliveryAction::class)->handle(
            company: $this->company,
            data: $this->orderData(),
            rider: $this->rider,
        );
    }

    private function orderData(): OrderData
    {
        return new OrderData(
            pickup: new LocationSnapshot(
                contactName: 'محل الورد',
                contactPhone: '01000000001',
                addressLine: 'وسط البلد',
                area: $this->zone->name,
                city: 'Banha',
                latitude: 30.4610,
                longitude: 31.1840,
                zoneId: $this->zone->id,
            ),
            dropoff: new LocationSnapshot(
                contactName: 'سارة محمود',
                contactPhone: '01000000002',
                addressLine: '١٢ شارع الجلاء',
                area: $this->zone->name,
                city: 'Banha',
                latitude: 30.4560,
                longitude: 31.1900,
                zoneId: $this->zone->id,
            ),
            paymentType: PaymentType::Prepaid,
            reference: CreateExternalDeliveryAction::reference(),
        );
    }
}
