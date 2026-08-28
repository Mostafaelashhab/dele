<?php

return [
    'action' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'status_changed' => 'Status changed',
        'suspended' => 'Suspended',
        'reinstated' => 'Reinstated',
        'pricing_changed' => 'Pricing changed',
        'offer_sent' => 'Offer sent',
        'offer_accepted' => 'Offer accepted',
        'offer_rejected' => 'Offer rejected',
        'rider_assigned' => 'Rider assigned',
        'settlement_created' => 'Settlement created',
        'settlement_paid' => 'Settlement paid',
        'api_key_issued' => 'API key issued',
        'api_key_revoked' => 'API key revoked',
        'logged_in' => 'Signed in',
        'login_failed' => 'Failed sign-in',
    ],

    'description' => [
        'offer_accepted' => ':company accepted order :order',
        'offer_rejected' => ':company rejected the offer',
        'rider_assigned' => ':rider was assigned to order :order',
        'delivery_cancelled' => 'Order :order was cancelled — :reason',
        'company_suspended' => 'Delivery company :company was suspended',
        'pricing_updated' => 'Pricing rule :rule was updated',
        'settlement_created' => 'Settlement :reference was created',
    ],
];
