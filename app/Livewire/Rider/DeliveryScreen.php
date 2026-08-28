<?php

namespace App\Livewire\Rider;

use App\Actions\Deliveries\AdvanceDeliveryAction;
use App\Actions\Deliveries\RespondToAssignmentAction;
use App\Domain\Proof\ConfirmationResult;
use App\Domain\Proof\DeliveryConfirmationCode;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\AssignmentStatus;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryAssignment;
use App\Models\Rider;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

/**
 * The single delivery a rider is working on.
 *
 * One screen, one obvious next action. The button shown is derived from the
 * delivery's state rather than from local flags, so a stale tab or a
 * double-tap can never advance the delivery twice.
 */
class DeliveryScreen extends Component
{
    use WithFileUploads;

    public const MAP_ID = 'rider-delivery';

    public string $deliveryId = '';

    public string $receivedBy = '';

    public string $codCollected = '';

    public string $failureReason = '';

    public bool $confirming = false;

    /**
     * Proof of delivery, taken on the rider's phone.
     *
     * The browser downscales before upload, so what arrives here is already a
     * sensible size even though the camera produced several megabytes.
     */
    public $proofPhoto = null;

    public $proofPhotoSecondary = null;

    public string $confirmAction = '';

    /**
     * The code the recipient reads off their tracking page.
     *
     * Checked on its own request rather than at submit time, so the rider
     * finds out at the doorstep whether it was heard correctly instead of
     * after filling in the rest of the form.
     */
    public string $confirmationCode = '';

    public ?string $codeFeedback = null;

    public bool $codeAccepted = false;

    public function mount(string $delivery): void
    {
        $model = Delivery::query()
            ->where('public_id', $delivery)
            ->firstOrFail();

        // A rider may only open a delivery that is theirs, or one they are
        // currently being offered.
        abort_unless(
            $model->rider_id === $this->rider()->id || $this->offeredAssignmentFor($model) !== null,
            403,
        );

        $this->deliveryId = $model->id;
        $this->codCollected = (string) (($model->order->cod_amount_minor?->minor ?? 0) / 100);
    }

    public function rider(): Rider
    {
        return app(CurrentTenant::class)->riderOrFail();
    }

    #[Computed]
    public function delivery(): Delivery
    {
        return Delivery::query()
            ->whereKey($this->deliveryId)
            ->with(['order', 'business', 'deliveryCompany'])
            ->firstOrFail();
    }

    #[Computed]
    public function assignment(): ?DeliveryAssignment
    {
        return DeliveryAssignment::query()
            ->where('delivery_id', $this->deliveryId)
            ->where('rider_id', $this->rider()->id)
            ->whereIn('status', [AssignmentStatus::Offered->value, AssignmentStatus::Accepted->value])
            ->latest('offered_at')
            ->first();
    }

    public function acceptAssignment(): void
    {
        $assignment = $this->assignment();

        if ($assignment === null || $assignment->status !== AssignmentStatus::Offered) {
            return;
        }

        $this->run(fn () => app(RespondToAssignmentAction::class)->accept($assignment));
    }

    public function rejectAssignment(): void
    {
        $assignment = $this->assignment();

        if ($assignment === null || $assignment->status !== AssignmentStatus::Offered) {
            return;
        }

        app(RespondToAssignmentAction::class)->reject($assignment, 'declined_by_rider');

        $this->redirectRoute('rider.home', navigate: true);
    }

    public function arrivedAtPickup(): void
    {
        $this->run(fn () => app(AdvanceDeliveryAction::class)
            ->arrivedAtPickup($this->delivery(), $this->rider()));
    }

    public function confirmPickup(): void
    {
        $this->run(fn () => app(AdvanceDeliveryAction::class)
            ->pickedUp($this->delivery(), $this->rider()));
    }

    public function startTransit(): void
    {
        $this->run(fn () => app(AdvanceDeliveryAction::class)
            ->startTransit($this->delivery(), $this->rider()));
    }

    public function arrivedAtDestination(): void
    {
        $this->run(fn () => app(AdvanceDeliveryAction::class)
            ->arrivedAtDestination($this->delivery(), $this->rider()));
    }

    /**
     * Check the recipient's code.
     *
     * Every outcome is spoken back to the rider, including how many tries are
     * left, because the alternative is a rider tapping a dead button at
     * someone's door with no idea why.
     */
    public function verifyConfirmationCode(): void
    {
        $delivery = $this->delivery();

        $result = app(DeliveryConfirmationCode::class)->verify($delivery, $this->confirmationCode);

        $this->codeAccepted = $result->isVerified();
        $this->codeFeedback = $result->message();

        if ($result === ConfirmationResult::Incorrect) {
            $fresh = $delivery->fresh();

            $this->codeFeedback .= ' '.trans_choice(
                'rider.proof.attempts_left',
                $fresh->confirmationAttemptsLeft(),
                ['count' => $fresh->confirmationAttemptsLeft()],
            );
        }

        if ($this->codeAccepted) {
            $this->confirmationCode = '';
        }
    }

    public function confirmDelivered(): void
    {
        $delivery = $this->delivery();

        $maxKb = (int) config('platform.media.max_upload_kb', 4096);

        $rules = [
            'receivedBy' => ['nullable', 'string', 'max:120'],
            'proofPhoto' => ['nullable', 'image', 'max:'.$maxKb],
            'proofPhotoSecondary' => ['nullable', 'image', 'max:'.$maxKb],
        ];

        if ($delivery->order->payment_type->requiresCollection()) {
            $rules['codCollected'] = ['required', 'numeric', 'min:0'];
        }

        $this->validate($rules);

        $collected = $delivery->order->payment_type->requiresCollection()
            ? Money::ofMajor($this->codCollected)
            : null;

        // Photos are written before the transition, so a delivery that
        // reaches "delivered" always has its evidence attached rather than
        // acquiring it a moment later.
        $proofPath = $this->storeProof($delivery, 'proof_photo_path', $this->proofPhoto);
        $this->storeProof($delivery, 'proof_photo_secondary_path', $this->proofPhotoSecondary);

        $this->run(fn () => app(AdvanceDeliveryAction::class)->delivered(
            delivery: $delivery->fresh(),
            rider: $this->rider(),
            receivedBy: $this->receivedBy !== '' ? $this->receivedBy : null,
            proofPhotoPath: $proofPath,
            codCollected: $collected,
        ));

        $this->redirectRoute('rider.home', navigate: true);
    }

    public function reportFailure(): void
    {
        $this->validate(['failureReason' => ['required', 'string', 'max:200']]);

        $this->run(fn () => app(AdvanceDeliveryAction::class)->failed(
            delivery: $this->delivery(),
            rider: $this->rider(),
            reason: $this->failureReason,
        ));

        $this->redirectRoute('rider.home', navigate: true);
    }

    /**
     * The one action offered right now, derived from state.
     *
     * @return array{method: string, label: string, confirm: ?string}|null
     */
    #[Computed]
    public function nextAction(): ?array
    {
        return match ($this->delivery()->status) {
            DeliveryStatus::Assigned => [
                'method' => 'arrivedAtPickup',
                'label' => __('rider.app.arrived_pickup'),
                'confirm' => null,
            ],
            DeliveryStatus::ArrivedAtPickup => [
                'method' => 'confirmPickup',
                'label' => __('rider.app.picked_up'),
                'confirm' => __('rider.app.confirm_pickup'),
            ],
            DeliveryStatus::PickedUp => [
                'method' => 'startTransit',
                'label' => __('rider.app.start_delivery'),
                'confirm' => null,
            ],
            DeliveryStatus::InTransit => [
                'method' => 'arrivedAtDestination',
                'label' => __('rider.app.arrived_customer'),
                'confirm' => null,
            ],
            default => null,
        };
    }

    /**
     * Pickup and dropoff plotted, with the leg drawn between them.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapConfig(): array
    {
        $delivery = $this->delivery();

        return [
            'markers' => MapPayload::legFor($delivery),
            'route' => MapPayload::routeFor($delivery),
        ];
    }

    public function hasMap(): bool
    {
        return ($this->mapConfig()['markers'] ?? []) !== [];
    }

    /**
     * Persist one proof image, if the rider attached it.
     */
    private function storeProof(Delivery $delivery, string $attribute, mixed $upload): ?string
    {
        if (! $upload instanceof TemporaryUploadedFile) {
            return $delivery->{$attribute};
        }

        return $delivery->storeMedia($attribute, $upload, 'proof');
    }

    private function offeredAssignmentFor(Delivery $delivery): ?DeliveryAssignment
    {
        return DeliveryAssignment::query()
            ->where('delivery_id', $delivery->id)
            ->where('rider_id', $this->rider()->id)
            ->where('status', AssignmentStatus::Offered)
            ->first();
    }

    /**
     * Domain failures here are expected traffic, not bugs — an offer taken by
     * someone else, a tap that arrived after a timeout — so they surface as a
     * message on the rider's screen rather than an error page.
     */
    private function run(callable $operation): void
    {
        try {
            $operation();
        } catch (Throwable $exception) {
            $this->dispatch('rider-error', message: $exception->getMessage());

            return;
        }

        unset($this->delivery, $this->assignment, $this->nextAction);
    }

    public function render(): View
    {
        return view('livewire.rider.delivery-screen')
            ->layout('components.layouts.rider', [
                'title' => $this->delivery()->order->number,
            ]);
    }
}
