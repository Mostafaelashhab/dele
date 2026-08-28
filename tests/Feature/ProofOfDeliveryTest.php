<?php

namespace Tests\Feature;

use App\Actions\Deliveries\AdvanceDeliveryAction;
use App\Domain\Proof\ConfirmationResult;
use App\Domain\Proof\DeliveryConfirmationCode;
use App\Domain\Tracking\TrackingPresenter;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * The handover code and the photograph are the two ways a delivery proves it
 * happened. These tests pin the promise the platform makes about them: a
 * delivery does not close without one, the code only works for the person
 * holding the tracking link, and it cannot be brute-forced.
 */
class ProofOfDeliveryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_delivery_is_issued_a_code_when_it_is_created(): void
    {
        $delivery = Delivery::factory()->create();

        $this->assertNotNull($delivery->confirmation_code);
        $this->assertSame(
            (int) config('platform.proof.code_digits'),
            strlen($delivery->confirmation_code)
        );
        $this->assertTrue($delivery->confirmationCodeAvailable());
        $this->assertFalse($delivery->hasProofOfDelivery());
    }

    #[Test]
    public function the_right_code_records_the_handover(): void
    {
        $delivery = Delivery::factory()->create();

        $result = app(DeliveryConfirmationCode::class)
            ->verify($delivery, $delivery->confirmation_code);

        $this->assertSame(ConfirmationResult::Verified, $result);
        $this->assertNotNull($delivery->fresh()->confirmation_code_verified_at);
        $this->assertTrue($delivery->fresh()->hasProofOfDelivery());
    }

    #[Test]
    public function a_wrong_code_is_rejected_and_spends_an_attempt(): void
    {
        $delivery = Delivery::factory()->create();
        $wrong = str_pad((string) (((int) $delivery->confirmation_code + 1) % 10000), 4, '0', STR_PAD_LEFT);

        $result = app(DeliveryConfirmationCode::class)->verify($delivery, $wrong);

        $this->assertSame(ConfirmationResult::Incorrect, $result);
        $this->assertSame(1, $delivery->fresh()->confirmation_attempts);
        $this->assertNull($delivery->fresh()->confirmation_code_verified_at);
    }

    /**
     * Four digits is only safe because guessing is bounded. Without this the
     * code would be a formality rather than a control.
     */
    #[Test]
    public function the_code_stops_being_an_option_after_the_attempt_limit(): void
    {
        $delivery = Delivery::factory()->create();
        $service = app(DeliveryConfirmationCode::class);
        $max = DeliveryConfirmationCode::maxAttempts();

        for ($attempt = 1; $attempt < $max; $attempt++) {
            $service->verify($delivery->fresh(), 'x');
        }

        $this->assertSame(ConfirmationResult::LockedOut, $service->verify($delivery->fresh(), 'x'));
        $this->assertFalse($delivery->fresh()->confirmationCodeAvailable());

        // Even the correct code no longer works once the budget is spent.
        $this->assertSame(
            ConfirmationResult::LockedOut,
            $service->verify($delivery->fresh(), $delivery->confirmation_code)
        );
    }

    #[Test]
    public function non_digits_are_ignored_so_a_spaced_code_still_works(): void
    {
        $delivery = Delivery::factory()->create();
        $spaced = implode(' ', str_split($delivery->confirmation_code));

        $this->assertSame(
            ConfirmationResult::Verified,
            app(DeliveryConfirmationCode::class)->verify($delivery, $spaced)
        );
    }

    /**
     * The code is for the doorstep, not for the record. Showing it after the
     * fact would leave a live-looking secret on a link that gets forwarded.
     */
    #[Test]
    public function the_code_is_shown_to_the_customer_only_while_a_rider_carries_the_parcel(): void
    {
        $delivery = Delivery::factory()->create();
        $presenter = app(TrackingPresenter::class);

        $delivery->forceFill(['status' => DeliveryStatus::Searching])->save();
        $this->assertNull($presenter->present($delivery->fresh())['confirmation_code']);

        $delivery->forceFill(['status' => DeliveryStatus::InTransit])->save();
        $this->assertSame(
            $delivery->confirmation_code,
            $presenter->present($delivery->fresh())['confirmation_code']
        );

        $delivery->forceFill([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now(),
        ])->save();
        $this->assertNull($presenter->present($delivery->fresh())['confirmation_code']);
    }

    #[Test]
    public function a_verified_code_counts_as_proof_and_a_photo_counts_as_proof(): void
    {
        $byCode = Delivery::factory()->create();
        app(DeliveryConfirmationCode::class)->verify($byCode, $byCode->confirmation_code);
        $this->assertTrue($byCode->fresh()->hasProofOfDelivery());

        $byPhoto = Delivery::factory()->create();
        $byPhoto->forceFill(['proof_photo_path' => 'proof/whatever.jpg'])->save();
        $this->assertTrue($byPhoto->fresh()->hasProofOfDelivery());
    }

    /**
     * The guarantee the shop is actually buying.
     */
    #[Test]
    public function a_delivery_cannot_be_closed_with_no_evidence_at_all(): void
    {
        $delivery = $this->deliveryAtTheDoor();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__('rider.proof.required'));

        app(AdvanceDeliveryAction::class)->delivered(
            delivery: $delivery,
            rider: $delivery->rider,
        );
    }

    #[Test]
    public function a_verified_code_lets_the_delivery_close(): void
    {
        $delivery = $this->deliveryAtTheDoor();

        app(DeliveryConfirmationCode::class)->verify($delivery, $delivery->confirmation_code);

        $closed = app(AdvanceDeliveryAction::class)->delivered(
            delivery: $delivery->fresh(),
            rider: $delivery->rider,
            receivedBy: 'سارة محمود',
        );

        $this->assertSame(DeliveryStatus::Delivered, $closed->status);
        $this->assertTrue($closed->hasProofOfDelivery());
    }

    #[Test]
    public function a_photo_alone_also_lets_the_delivery_close(): void
    {
        $delivery = $this->deliveryAtTheDoor();

        $closed = app(AdvanceDeliveryAction::class)->delivered(
            delivery: $delivery,
            rider: $delivery->rider,
            proofPhotoPath: 'proof/at-the-door.jpg',
        );

        $this->assertSame(DeliveryStatus::Delivered, $closed->status);
        $this->assertTrue($closed->hasProofOfDelivery());
    }

    /**
     * A network that does not want the requirement can switch it off, and the
     * switch has to actually work.
     */
    #[Test]
    public function the_requirement_can_be_turned_off_by_configuration(): void
    {
        config(['platform.proof.require_at_delivery' => false]);

        $delivery = $this->deliveryAtTheDoor();

        $closed = app(AdvanceDeliveryAction::class)->delivered(
            delivery: $delivery,
            rider: $delivery->rider,
        );

        $this->assertSame(DeliveryStatus::Delivered, $closed->status);
        $this->assertFalse($closed->hasProofOfDelivery());
    }

    /**
     * A delivery parked at the customer's door with a rider on it, which is
     * the only state `delivered()` may be called from.
     */
    private function deliveryAtTheDoor(): Delivery
    {
        $delivery = Delivery::factory()->arrivedAtDestination()->create();

        $this->assertNotNull($delivery->rider, 'The factory state must attach a rider.');

        return $delivery;
    }
}
