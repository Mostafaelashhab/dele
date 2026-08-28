@props(['type'])

@php
    /**
     * A symbol per stage of the journey.
     *
     * The page used to draw the same tick against every step, so the list said
     * only "done, done, done" — the shape carried no information and the reader
     * had to fall back on the words. A parcel being collected and a rider
     * reaching the door are different events and should look different, so the
     * journey can be read at a glance.
     *
     * Drawn inline rather than taken from the icon set because several of these
     * stages have no general-purpose icon: "the rider reached the shop" is not
     * something an icon library has a name for.
     */
    $paths = [
        'OrderCreated' => '<path d="M6 3h12a1 1 0 0 1 1 1v17l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z"/><path d="M9 8h6M9 12h6"/>',
        'DeliveryAccepted' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5L15.5 10"/>',
        'DeliveryCompanyOffered' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'RiderAssigned' => '<circle cx="12" cy="8" r="4"/><path d="M5 21v-1a7 7 0 0 1 14 0v1"/>',
        'RiderArrivedAtPickup' => '<path d="m3 7 1.5-3h15L21 7"/><path d="M3 7h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0Z"/><path d="M5 12v8h14v-8"/>',
        'OrderPickedUp' => '<path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'DeliveryStarted' => '<circle cx="18" cy="17.5" r="3"/><circle cx="6" cy="17.5" r="3"/><path d="M15 5.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
        'RiderArrived' => '<path d="M20 10c0 5-6.4 10.5-7.4 11.3a1 1 0 0 1-1.2 0C10.4 20.5 4 15 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'OrderDelivered' => '<path d="M20 6 9 17l-5-5"/>',
        'OrderFailed' => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
        'OrderCancelled' => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
        'DeliveryExpired' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'NoCompanyAvailable' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
    ];

    // Anything unmapped falls back to a dot: an honest "something happened"
    // rather than a symbol that means the wrong thing.
    $shape = $paths[$type] ?? '<circle cx="12" cy="12" r="3.5"/>';
@endphp

<svg {{ $attributes->merge(['class' => 'size-4']) }} viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true">{!! $shape !!}</svg>
