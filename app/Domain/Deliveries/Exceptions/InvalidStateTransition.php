<?php

namespace App\Domain\Deliveries\Exceptions;

use App\Enums\DeliveryStatus;
use DomainException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InvalidStateTransition extends DomainException
{
    public function __construct(
        string $message,
        public readonly ?DeliveryStatus $from = null,
        public readonly ?DeliveryStatus $to = null,
    ) {
        parent::__construct($message);
    }

    public static function between(DeliveryStatus $from, DeliveryStatus $to): self
    {
        return new self(
            "A delivery cannot move from [{$from->value}] to [{$to->value}].",
            $from,
            $to,
        );
    }

    /**
     * Surfaced to API clients as a 409: the request was well formed, but the
     * delivery is no longer in a state where it makes sense.
     */
    public function render(): Response
    {
        return response()->json([
            'error' => [
                'type' => 'invalid_state_transition',
                'message' => __('delivery.errors.invalid_transition', [
                    'from' => $this->from?->label() ?? '—',
                    'to' => $this->to?->label() ?? '—',
                ]),
                'from' => $this->from?->value,
                'to' => $this->to?->value,
            ],
        ], 409);
    }

    public function report(): bool
    {
        Log::warning('Rejected delivery state transition.', [
            'from' => $this->from?->value,
            'to' => $this->to?->value,
        ]);

        return false;
    }
}
