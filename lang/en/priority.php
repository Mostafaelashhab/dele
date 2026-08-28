<?php

/**
 * Copy for the three delivery priorities as presented publicly.
 *
 * The prices beside these come from the live pricing engine, so the words
 * only describe what each priority actually changes — nothing here promises
 * a service level the dispatcher does not implement.
 */
return [
    'standard' => [
        'body' => 'Ordinary delivery within Banha. Most orders go this way.',
        'points' => [
            'Offered to every available company',
            'Live tracking for your customer',
            'Photo proof of delivery',
            'Prepaid or cash on delivery',
        ],
    ],
    'express' => [
        'body' => 'For an order that cannot wait — offered wider and ranked by speed.',
        'points' => [
            'Offered to more companies at once',
            'Ranked by arrival time, not price',
            'The nearest rider is preferred',
            'Everything in standard delivery',
        ],
    ],
    'scheduled' => [
        'body' => 'For orders that are not urgent, priced lower because the network can place them in a quieter hour.',
        'points' => [
            'Cheaper than standard delivery',
            'Suits batched orders',
            'Same tracking and proof of delivery',
            'You choose the collection time',
        ],
    ],
];
