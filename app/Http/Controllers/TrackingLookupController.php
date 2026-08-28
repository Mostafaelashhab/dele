<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Finds a delivery from an order number and the recipient's phone.
 *
 * The tracking token in a customer's SMS is long and unguessable precisely so
 * that a link is safe to pass around. An order number is not: it is short and
 * human-quotable by design, so it alone must never open somebody's address.
 *
 * Requiring the recipient's own phone number turns the lookup into something
 * only the recipient (or the shop that sent it) can do, and the throttle makes
 * grinding through the small number space impractical.
 */
class TrackingLookupController extends Controller
{
    private const MAX_ATTEMPTS = 6;

    private const DECAY_SECONDS = 300;

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'max:20'],
        ], [], [
            'number' => __('tracking.lookup.number'),
            'phone' => __('tracking.lookup.phone'),
        ]);

        // Keyed on the address rather than the order number: a scan walks
        // through many numbers from one place, and that is what has to stop.
        $throttleKey = 'tracking-lookup:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'number' => __('tracking.lookup.throttled', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($throttleKey) / 60),
                ]),
            ]);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        $order = Order::query()
            ->where('number', mb_strtoupper(trim($validated['number'])))
            ->with('currentDelivery')
            ->first();

        $delivery = $order?->currentDelivery;

        // One message for "no such order" and for "wrong phone" alike: telling
        // them apart would confirm which order numbers exist.
        if ($delivery === null || ! $this->phoneMatches($order, $validated['phone'])) {
            throw ValidationException::withMessages([
                'number' => __('tracking.lookup.not_found'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        return redirect()->route('tracking.show', ['token' => $delivery->tracking_token]);
    }

    /**
     * Compares the given number against the recipient's, ignoring how it was
     * written — a shop owner reading it off a receipt may include the country
     * code, and the recipient never does.
     */
    private function phoneMatches(Order $order, string $given): bool
    {
        $normalise = function (string $phone): string {
            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            if (str_starts_with($digits, '20')) {
                $digits = mb_substr($digits, 2);
            }

            return ltrim($digits, '0');
        };

        return hash_equals(
            $normalise($order->dropoffSnapshot()->contactPhone),
            $normalise($given),
        );
    }
}
