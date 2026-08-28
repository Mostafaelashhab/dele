<?php

return [
    'channel' => [
        'database' => 'In-app',
        'mail' => 'Email',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
        'push' => 'Push',
        'broadcast' => 'Live',
    ],

    'sms' => [
        'offer_received' => 'New delivery :order from :area — payout :amount. Open your dashboard to respond.',
        'delivery_accepted' => 'Order :order was accepted by :company. A rider is being assigned.',
        'rider_assignment' => 'New delivery :order — payout :amount. Open the app to accept.',
        'delivery_progressed' => 'Order :order update: :status',
        'customer_picked_up' => 'Your order from :business is on its way. Track it: :url',
        'customer_arriving' => 'Your rider has arrived at your address.',
        'customer_delivered' => 'Your order from :business has been delivered. Thank you.',
        'customer_update' => 'Order update: :status — :url',
    ],

    'empty' => 'No new notifications.',
    'mark_all_read' => 'Mark all as read',
];
