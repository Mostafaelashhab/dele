<?php

return [
    /*
     * The handover code, from the recipient's side. Shown only while a rider
     * is actually carrying the parcel.
     */
    'code' => [
        'title' => 'Your handover code',
        'body' => 'Read this code to the rider when they arrive. Without it they cannot close the delivery.',
        'warning' => 'Give it to nobody but the rider handing you the parcel.',
        'proof_title' => 'Handover recorded',
        'proof_body' => 'This delivery was closed with proof — your code, or a photo of the parcel at handover.',
    ],

    'lookup' => [
        'title' => 'Track your delivery',
        'subtitle' => 'Enter the order number and the recipient’s phone to see where it is.',
        'number' => 'Order number',
        'number_placeholder' => 'BN260827-XXXXX',
        'phone' => 'Recipient’s phone',
        'phone_placeholder' => '01xxxxxxxxx',
        'submit' => 'Track',
        'not_found' => 'No delivery matches those details. Check the order number and phone.',
        'throttled' => 'Too many attempts. Try again in :minutes minutes.',
        'hint' => 'Got a link by SMS? Open it directly — no need to type anything.',
    ],
];
