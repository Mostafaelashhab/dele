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
        'proof_photos' => 'Delivery photos',
        'proof_photos_hint' => 'Taken by the rider when your parcel was handed over.',
        'proof_open' => 'Open full size',
        'proof_received_by' => 'Received by',
        'proof_by_code' => 'Confirmed with the handover code',
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
        'results_title' => 'Your parcels',
        'results_body' => 'Parcels linked to :phone over the last 30 days.',
        'results_empty' => 'No parcels linked to this number in the last 30 days.',
        'results_empty_hint' => 'Check the number, or ask the shop for the order number.',
        'results_open' => 'Track',
        'results_privacy' => 'Only the order number, the shop and the status are shown here. Address details appear inside the tracking page.',
        'results_from' => 'From',
        'search_again' => 'Search another number',
        'number_optional' => 'Order number (optional)',
        'phone_only_hint' => 'Do not know the order number? Enter your phone alone and we will list your parcels.',
        'hint' => 'Got a link by SMS? Open it directly — no need to type anything.',
    ],

    'issue' => [
        'actor' => 'Recipient',
        'trigger' => 'Report a problem',
        'trigger_hint' => 'Something wrong with this delivery? Tell us.',
        'title' => 'What went wrong?',
        'subtitle' => 'Pick what applies. Your report goes to the company handling this delivery and to the platform.',
        'note_label' => 'More detail (optional)',
        'note_placeholder' => 'Briefly, what happened.',
        'submit' => 'Send report',
        'cancel' => 'Cancel',
        'category_required' => 'Please choose what went wrong.',
        'throttled' => 'We already have your report. Please wait before sending another.',
        'closed' => 'This delivery is too old to report on here. Please contact the shop directly.',

        'received_title' => 'We have your report',
        'received_body' => 'The company handling this delivery has been told, and the platform has a record of it.',
        'reported_at' => 'Sent :time',
        'resolved_title' => 'Your report was handled',
        'acknowledged_title' => 'Your report is being looked at',
        'no_reply_yet' => 'No response yet.',
        'report_another' => 'Report another problem',

        'category' => [
            'late' => 'Far too late',
            'no_contact' => 'The courier is not answering',
            'wrong_address' => 'The address is wrong',
            'not_received' => 'Marked delivered, but I never got it',
            'damaged' => 'Damaged or items missing',
            'payment' => 'Something is wrong with the amount',
            'conduct' => 'The courier behaved badly',
            'other' => 'Something else',
        ],

        'status' => [
            'open' => 'New report',
            'acknowledged' => 'Being looked at',
            'resolved' => 'Handled',
        ],

        'panel_title' => 'Recipient reports',
        'panel_empty' => 'No reports on this delivery.',
        'panel_count' => '{1} one report|[2,*] :count reports',
        'open_count' => '{1} one open report|[2,*] :count open reports',
        'acknowledge' => 'Got it',
        'resolve' => 'Close report',
        'resolution_label' => 'What was done?',
        'resolution_placeholder' => 'Write what happened, so it shows on the order record.',
        'resolution_required' => 'Please say what was done before closing this.',
        'resolved_by' => 'Closed by :name',
        'reported_when' => 'Reported while the delivery was :status',
        'urgent' => 'Urgent',
    ],
];
