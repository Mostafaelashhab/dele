<?php

return [
    'account' => [
        'platform' => 'Platform',
        'business' => 'Business',
        'delivery_company' => 'Delivery company',
        'rider' => 'Rider',
        'customer' => 'Customer',
    ],

    'category' => [
        'delivery_fee' => 'Delivery fee',
        'platform_fee' => 'Platform fee',
        'company_payout' => 'Company payout',
        'rider_payout' => 'Rider payout',
        'business_charge' => 'Business charge',
        'cod_collected' => 'Cash collected',
        'cod_remittance' => 'Cash remittance',
        'commission' => 'Commission',
        'refund' => 'Refund',
        'adjustment' => 'Adjustment',
    ],

    'settlement' => [
        'draft' => 'Draft',
        'open' => 'Open',
        'locked' => 'Locked',
        'paid' => 'Paid',
        'voided' => 'Voided',
    ],

    'period' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'biweekly' => 'Fortnightly',
        'monthly' => 'Monthly',
    ],

    'description' => [
        'business_charge' => 'Delivery charge for order :order',
        'platform_fee' => 'Platform fee for order :order',
        'company_payout' => 'Delivery payout for order :order',
        'rider_payout' => 'Rider payout for order :order',
        'rider_earning' => 'Earnings for order :order',
        'cod_held' => 'Cash collected and held — order :order',
        'cod_owed' => 'Cash owed to business — order :order',
        'settlement_payout' => 'Settlement payout :reference',
    ],
];
