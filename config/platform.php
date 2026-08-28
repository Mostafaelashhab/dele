<?php

use App\Enums\DeliveryPriority;

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Identity
    |--------------------------------------------------------------------------
    */

    'name' => env('PLATFORM_NAME', 'Banha Delivery Network'),
    'city' => env('PLATFORM_CITY', 'Banha'),
    'country_code' => env('PLATFORM_COUNTRY', 'EG'),
    'timezone' => env('PLATFORM_TIMEZONE', 'Africa/Cairo'),

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    |
    | All monetary values are persisted as integer minor units (piastres).
    | Never introduce floating point arithmetic into financial code paths.
    |
    */

    'currency' => [
        'code' => env('PLATFORM_CURRENCY', 'EGP'),
        'minor_unit_scale' => 100,
        'symbol' => 'ج.م',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Engine Defaults
    |--------------------------------------------------------------------------
    |
    | These are fallbacks only. Real pricing is resolved from the pricing_rules
    | table so that operators can change prices without a deployment.
    |
    */

    'pricing' => [
        'default_base_minor' => (int) env('PRICING_DEFAULT_BASE_MINOR', 1500),
        'default_per_km_minor' => (int) env('PRICING_DEFAULT_PER_KM_MINOR', 300),
        'minimum_fare_minor' => (int) env('PRICING_MINIMUM_FARE_MINOR', 1500),
        'free_distance_meters' => (int) env('PRICING_FREE_DISTANCE_METERS', 1500),
        'rounding_increment_minor' => (int) env('PRICING_ROUNDING_INCREMENT_MINOR', 50),

        'priority_multiplier_bps' => [
            DeliveryPriority::Standard->value => 10000,
            DeliveryPriority::Express->value => 13000,
            DeliveryPriority::Scheduled->value => 9000,
        ],

        /*
         * The platform's cut, currently nothing.
         *
         * A percentage of each delivery is only collectable if the platform
         * sits in the payment flow — holding the money and remitting the rest
         * — and it does not. Invoicing dozens of small shops for a few pounds
         * of accumulated commission is a collections problem, not a revenue
         * model, and a fee on a price the platform never touches is trivially
         * avoided by agreeing the job off-platform.
         *
         * So during the pilot the network is free, and the pricing engine
         * still computes the split so nothing downstream has to special-case
         * a missing fee. Every line that would show a zero fee is hidden
         * rather than printed, so the product does not advertise a charge it
         * is not making.
         */
        'platform_fee' => [
            'percentage_bps' => (int) env('PLATFORM_FEE_BPS', 0),
            'fixed_minor' => (int) env('PLATFORM_FEE_FIXED_MINOR', 0),
            'minimum_minor' => (int) env('PLATFORM_FEE_MIN_MINOR', 0),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Matching Engine
    |--------------------------------------------------------------------------
    |
    | Weights must sum to 1.0. They are overridable per-environment and at
    | runtime through the platform_settings table.
    |
    */

    'matching' => [
        'strategy' => env('MATCHING_STRATEGY', 'weighted'),

        'weights' => [
            'distance' => 0.30,
            'availability' => 0.20,
            'price' => 0.20,
            'eta' => 0.15,
            'reliability' => 0.10,
            'acceptance_rate' => 0.05,
        ],

        'max_pickup_distance_meters' => (int) env('MATCHING_MAX_PICKUP_DISTANCE', 12000),
        'max_eta_minutes' => (int) env('MATCHING_MAX_ETA_MINUTES', 90),
        'preferred_company_bonus' => 0.15,
        'minimum_score' => 0.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Request Marketplace
    |--------------------------------------------------------------------------
    */

    'dispatch' => [
        'offer_timeout_seconds' => (int) env('DISPATCH_OFFER_TIMEOUT', 90),
        'companies_per_round' => (int) env('DISPATCH_COMPANIES_PER_ROUND', 2),
        'max_rounds' => (int) env('DISPATCH_MAX_ROUNDS', 4),
        'rider_offer_timeout_seconds' => (int) env('DISPATCH_RIDER_OFFER_TIMEOUT', 60),
        'requeue_delay_seconds' => (int) env('DISPATCH_REQUEUE_DELAY', 5),

        // How long a delivery may sit with no company and no open offer
        // before the sweeper puts it back into dispatch.
        'stalled_after_minutes' => (int) env('DISPATCH_STALLED_AFTER_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Live Tracking
    |--------------------------------------------------------------------------
    */

    'tracking' => [
        'ping_interval_seconds' => (int) env('TRACKING_PING_INTERVAL', 15),
        'minimum_ping_interval_seconds' => (int) env('TRACKING_MIN_PING_INTERVAL', 8),
        'minimum_movement_meters' => (int) env('TRACKING_MIN_MOVEMENT_METERS', 25),
        'retention_days' => (int) env('TRACKING_RETENTION_DAYS', 30),
        'token_bytes' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing / Distance
    |--------------------------------------------------------------------------
    |
    | The haversine driver needs no third party service and is accurate enough
    | for a single-city network. A road-network driver can be bound later
    | against the same DistanceCalculator contract.
    |
    */

    'routing' => [
        'driver' => env('ROUTING_DRIVER', 'haversine'),
        'road_factor' => (float) env('ROUTING_ROAD_FACTOR', 1.32),
        'average_speed_kmh' => (float) env('ROUTING_AVERAGE_SPEED_KMH', 22.0),
        'pickup_handling_minutes' => (int) env('ROUTING_PICKUP_HANDLING_MINUTES', 6),
        'dropoff_handling_minutes' => (int) env('ROUTING_DROPOFF_HANDLING_MINUTES', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    'webhooks' => [
        'timeout_seconds' => 10,
        'max_attempts' => 6,
        'backoff_seconds' => [30, 120, 600, 3600, 21600],
        'signature_header' => 'X-Banha-Signature',
        'timestamp_header' => 'X-Banha-Timestamp',
        'tolerance_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'api' => [
        'key_prefix' => env('API_KEY_PREFIX', 'bdn'),
        'default_rate_limit_per_minute' => (int) env('API_RATE_LIMIT', 120),
        'idempotency_ttl_hours' => 24,
        'log_retention_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | Images are written to a filesystem disk, never the database. The public
    | disk works on shared hosting through `php artisan storage:link`; swapping
    | this for S3 later needs no schema or code change.
    |
    */

    'media' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 4096),
        // Riders photograph proof of delivery on 3G. The browser downscales to
        // this edge before uploading, which is the difference between a
        // one-second upload and a stalled one.
        'proof_max_edge' => (int) env('MEDIA_PROOF_MAX_EDGE', 1400),
        'logo_max_edge' => (int) env('MEDIA_LOGO_MAX_EDGE', 512),
    ],

    /*
    |--------------------------------------------------------------------------
    | Settlements
    |--------------------------------------------------------------------------
    */

    /*
     * Proof of delivery.
     *
     * Two independent ways to show a handover happened: a photograph of the
     * parcel where it was left, and a short code the recipient reads off
     * their tracking page and says to the rider. Requiring at least one is
     * what turns "we take photos sometimes" into a guarantee a shop can
     * repeat to its own customer.
     */
    'proof' => [
        'require_at_delivery' => (bool) env('PROOF_REQUIRE_AT_DELIVERY', true),
        'code_digits' => (int) env('PROOF_CODE_DIGITS', 4),
        'code_max_attempts' => (int) env('PROOF_CODE_MAX_ATTEMPTS', 5),
    ],

    'settlements' => [
        'default_period' => env('SETTLEMENT_PERIOD', 'weekly'),
        // Free during the pilot; see the platform_fee note above.
        'company_commission_bps' => (int) env('COMPANY_COMMISSION_BPS', 0),
        'rider_share_bps' => (int) env('RIDER_SHARE_BPS', 7000),
    ],
];
