<?php

/**
 * Field labels.
 *
 * Every control in the product names itself from here. Interface labels used
 * to be borrowed from whichever key happened to read close enough — a sort
 * order labelled "Total", a company timeout labelled "Time left" — which is
 * how an interface starts feeling machine-assembled. A label belongs to the
 * field it names and to nothing else.
 */
return [
    // Orders
    'saved_address' => 'Use a saved address',
    'reference' => 'Your own reference',
    'reference_hint' => 'The order number in your system, so you can find it again',
    'order_notes' => 'Notes for the rider',
    'order_items' => 'What is in the order',
    'priority' => 'Delivery priority',
    'package_size' => 'Parcel size',
    'payment_method' => 'Payment method',
    'cod_amount' => 'Amount to collect',
    'pin_location' => 'Mark the customer on the map',
    'pin_done' => 'Location marked',
    'pin_hint' => 'Tap where the customer is — more precise than a written address',

    // Zones
    'zone_code' => 'Zone code',
    'zone_name_ar' => 'Zone name in Arabic',
    'zone_name_en' => 'Zone name in English',
    'zone_radius' => 'Zone radius',
    'zone_radius_hint' => 'In metres — how wide the zone reaches on the map',
    'zone_sort' => 'Display order',
    'zone_base_price' => 'Zone base price',
    'zone_eta' => 'Expected delivery time',
    'zone_centre_hint' => 'Tap the map to place the centre of the zone',

    // Riders
    'vehicle_type' => 'Vehicle type',
    'vehicle_plate' => 'Plate number',
    'max_concurrent_rider' => 'Most deliveries at once',
    'create_login' => 'Create a rider sign-in',
    'create_login_hint' => 'Lets them take deliveries from the rider app',

    // Companies
    'max_concurrent_company' => 'Most deliveries in flight',
    'offer_timeout' => 'How long you have to answer an offer',
    'offer_timeout_hint' => 'In seconds — after this the offer moves to the next company',
    'auto_assign' => 'Assign a rider automatically on accept',
    'auto_assign_hint' => 'The platform picks the nearest available rider instead of a dispatcher',
    'settlement_period' => 'Settlement cycle',
    'commission' => 'Platform commission',
    'commission_hint' => 'In basis points — 1200 is 12%',
    'working_hours' => 'Opening hours',
    'day' => 'Day',
    'closed' => 'Closed',
    'opens' => 'From',
    'closes' => 'To',

    // Matching & dispatch
    'matching_strategy' => 'How a delivery company is chosen',
    'matching_strategy_hint' => 'How the network ranks companies before offering to them',
    'matching_balanced' => 'Balanced (best overall)',
    'matching_cheapest' => 'Cheapest price',
    'matching_fastest' => 'Fastest arrival',
    'default_priority' => 'Default order priority',
    'companies_per_round' => 'Companies asked per round',
    'max_rounds' => 'Most search rounds',
    'rider_offer_timeout' => 'How long a rider has to answer',
    'ping_interval' => 'Rider location update rate',
    'ping_interval_hint' => 'In seconds — a higher number saves rider battery',
    'weights' => 'Company ranking weights',
    'weights_hint' => 'Normalised to 100% when you save',
    'weights_total' => 'Total before normalising',

    // Pricing
    'rule_name' => 'Rule name',
    'rule_type' => 'Rule type',
    'rule_amount' => 'Fixed amount',
    'rule_rate' => 'Rate per kilometre',
    'rule_percentage' => 'Percentage',
    'rule_percentage_hint' => 'Applied to the running subtotal — negative is a discount',
    'rule_free_distance' => 'Free distance',
    'rule_free_distance_hint' => 'In metres — not charged as distance',
    'rule_pickup_zone' => 'Pickup zone',
    'rule_dropoff_zone' => 'Dropoff zone',
    'rule_active' => 'Rule is active',
    'platform_fee' => 'Platform fee',
    'rider_share' => "Rider's share of the company payout",

    // Finance
    'payment_reference' => 'Bank transfer reference',
    'payment_reference_hint' => 'Optional — makes reconciliation easier later',
    'period_start' => 'Period start',
    'period_end' => 'Period end',

    // Team & API
    'team_role' => 'Member role',
    'api_client_name' => 'Application name',
    'api_client_name_hint' => 'Helps you tell your keys apart later',
    'webhook_url' => 'Endpoint URL',
    'webhook_events' => 'Events to send',
    'webhook_secret_notice' => 'Copy this now — it will not be shown again',

    // Proof of delivery
    'proof_primary' => 'Proof of delivery photo',
    'proof_secondary' => 'Second photo (optional)',
    'received_by' => 'Who received it',

    // Charts
    'chart_table_view' => 'Show the numbers as a table',
    'chart_more_rows' => 'and :count more not shown',
    'meter_healthy' => 'On target',
    'meter_watch' => 'Worth watching',
    'meter_low' => 'Below target',
];
