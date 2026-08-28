<?php

namespace Tests\Unit;

use App\Domain\Deliveries\DeliveryStateMachine;
use App\Domain\Deliveries\Exceptions\InvalidStateTransition;
use App\Enums\DeliveryStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The state machine is the guard rail that makes an incoherent delivery — one
 * delivered before it was collected — unrepresentable rather than merely
 * unlikely. These tests pin the transition table down.
 */
class DeliveryStateMachineTest extends TestCase
{
    private DeliveryStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->machine = new DeliveryStateMachine;
    }

    #[Test]
    public function it_walks_the_happy_path(): void
    {
        $path = [
            DeliveryStatus::Draft,
            DeliveryStatus::Pending,
            DeliveryStatus::Searching,
            DeliveryStatus::Offered,
            DeliveryStatus::Accepted,
            DeliveryStatus::Assigned,
            DeliveryStatus::ArrivedAtPickup,
            DeliveryStatus::PickedUp,
            DeliveryStatus::InTransit,
            DeliveryStatus::ArrivedAtDestination,
            DeliveryStatus::Delivered,
        ];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue(
                $this->machine->canTransition($path[$i], $path[$i + 1]),
                "{$path[$i]->value} should be able to reach {$path[$i + 1]->value}.",
            );
        }
    }

    #[Test]
    public function a_delivery_cannot_skip_pickup(): void
    {
        // The single most damaging illegal move: marking something delivered
        // that no rider ever collected.
        $this->assertFalse(
            $this->machine->canTransition(DeliveryStatus::Assigned, DeliveryStatus::Delivered)
        );

        $this->assertFalse(
            $this->machine->canTransition(DeliveryStatus::Searching, DeliveryStatus::PickedUp)
        );
    }

    #[Test]
    public function a_parcel_in_a_riders_hands_can_no_longer_be_cancelled(): void
    {
        // Past pickup the only honest outcomes are delivered or failed;
        // "cancelled" would leave a real parcel unaccounted for.
        $this->assertFalse(
            $this->machine->canTransition(DeliveryStatus::PickedUp, DeliveryStatus::Cancelled)
        );

        $this->assertFalse(
            $this->machine->canTransition(DeliveryStatus::InTransit, DeliveryStatus::Cancelled)
        );

        $this->assertTrue(
            $this->machine->canTransition(DeliveryStatus::InTransit, DeliveryStatus::Failed)
        );
    }

    #[Test]
    public function a_cancellable_delivery_can_be_cancelled_before_pickup(): void
    {
        foreach ([
            DeliveryStatus::Draft,
            DeliveryStatus::Pending,
            DeliveryStatus::Searching,
            DeliveryStatus::Offered,
            DeliveryStatus::Accepted,
            DeliveryStatus::Assigned,
            DeliveryStatus::ArrivedAtPickup,
        ] as $status) {
            $this->assertTrue(
                $this->machine->canTransition($status, DeliveryStatus::Cancelled),
                "{$status->value} should still be cancellable.",
            );
        }
    }

    #[Test]
    public function a_rejected_round_returns_the_delivery_to_the_marketplace(): void
    {
        // Every company declining is not a failure; the dispatcher widens the
        // pool and searches again.
        $this->assertTrue(
            $this->machine->canTransition(DeliveryStatus::Offered, DeliveryStatus::Searching)
        );

        // A company that hands work back before a rider is on it likewise.
        $this->assertTrue(
            $this->machine->canTransition(DeliveryStatus::Accepted, DeliveryStatus::Searching)
        );
    }

    #[Test]
    public function a_rider_rejection_hands_the_delivery_back_to_the_company(): void
    {
        $this->assertTrue(
            $this->machine->canTransition(DeliveryStatus::Assigned, DeliveryStatus::Accepted)
        );
    }

    #[DataProvider('terminalStatuses')]
    #[Test]
    public function terminal_statuses_are_final(DeliveryStatus $status): void
    {
        $this->assertSame([], $this->machine->allowedFrom($status));
        $this->assertTrue($status->isTerminal());

        foreach (DeliveryStatus::cases() as $target) {
            $this->assertFalse(
                $this->machine->canTransition($status, $target),
                "{$status->value} must not reach {$target->value}.",
            );
        }
    }

    /**
     * @return array<string, array{0: DeliveryStatus}>
     */
    public static function terminalStatuses(): array
    {
        return [
            'delivered' => [DeliveryStatus::Delivered],
            'failed' => [DeliveryStatus::Failed],
            'cancelled' => [DeliveryStatus::Cancelled],
            'expired' => [DeliveryStatus::Expired],
        ];
    }

    #[Test]
    public function asserting_an_illegal_transition_throws(): void
    {
        $this->expectException(InvalidStateTransition::class);

        $this->machine->assertCanTransition(DeliveryStatus::Delivered, DeliveryStatus::InTransit);
    }

    #[Test]
    public function every_status_stamps_the_column_that_describes_it(): void
    {
        $this->assertSame('picked_up_at', $this->machine->timestampColumn(DeliveryStatus::PickedUp));
        $this->assertSame('delivered_at', $this->machine->timestampColumn(DeliveryStatus::Delivered));
        $this->assertSame('cancelled_at', $this->machine->timestampColumn(DeliveryStatus::Cancelled));

        // Expiry is a kind of failure, and shares its timestamp column.
        $this->assertSame('failed_at', $this->machine->timestampColumn(DeliveryStatus::Expired));
    }

    #[Test]
    public function the_transition_table_covers_every_status(): void
    {
        // A status added to the enum without a transition entry would silently
        // become a dead end, so the table is checked for completeness.
        foreach (DeliveryStatus::cases() as $status) {
            $this->assertArrayHasKey(
                $status->value,
                DeliveryStateMachine::transitions(),
                "No transition rule defined for [{$status->value}].",
            );
        }
    }
}
