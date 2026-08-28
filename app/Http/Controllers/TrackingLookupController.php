<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Finds a delivery for somebody who is waiting for one.
 *
 * There are two ways in, and they deliberately reveal different amounts.
 *
 * With an order number *and* the recipient's phone, the lookup opens the
 * tracking page itself. The order number alone is short and human-quotable by
 * design, so it must never open somebody's address on its own; pairing it with
 * the phone makes the lookup something only the recipient or the shop can do.
 *
 * With the phone alone it returns a list and nothing more: order number, shop,
 * status. No addresses, no names, no rider. That restraint is the point — this
 * network sends no SMS today, so a recipient may never have received a link at
 * all, and refusing to help them find their own parcel would be worse. But a
 * phone number is not a password: anyone who knows yours could ask. So the
 * list says only enough to recognise your own order and go on to it, and the
 * step that reveals an address still asks for the number.
 */
class TrackingLookupController extends Controller
{
    private const MAX_ATTEMPTS = 6;

    private const DECAY_SECONDS = 300;

    /** How far back a phone-only search looks. */
    private const HISTORY_DAYS = 30;

    private const MAX_RESULTS = 10;

    public function __invoke(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            // The number is optional: somebody who was never sent a link has
            // no way of knowing it.
            'number' => ['nullable', 'string', 'max:32'],
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

        if (blank($validated['number'] ?? null)) {
            return $this->listForPhone($validated['phone'], $throttleKey);
        }

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
     * Every live delivery for one phone number, told as little as possible.
     *
     * Matching happens in PHP rather than SQL because the recipient's phone
     * lives inside the order's address snapshot and has to be compared in its
     * normalised form — a shop may have typed the country code, the recipient
     * never does.
     */
    private function listForPhone(string $phone, string $throttleKey): View
    {
        $matches = Order::query()
            ->where('created_at', '>=', now()->subDays(self::HISTORY_DAYS))
            ->whereHas('currentDelivery')
            ->with(['currentDelivery', 'business'])
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->filter(fn (Order $order) => $this->phoneMatches($order, $phone))
            ->take(self::MAX_RESULTS)
            ->values();

        if ($matches->isNotEmpty()) {
            RateLimiter::clear($throttleKey);
        }

        return view('public.tracking-results', [
            'phone' => $phone,
            'orders' => $matches->map(fn (Order $order) => [
                'number' => $order->number,
                'business' => $order->business->displayName(),
                'status' => $order->currentDelivery->status->label(),
                'tone' => $order->currentDelivery->status->tone(),
                'placed' => $order->created_at,
                // The token is what opens the full page, and this list only
                // reaches somebody who proved they own the phone the parcel is
                // addressed to.
                'token' => $order->currentDelivery->tracking_token,
            ]),
        ]);
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
