<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission
    |--------------------------------------------------------------------------
    |
    | The MVP charges 0% while validating the marketplace, but every completed
    | shipment still records a commission row (the "shadow ledger") so Phase 2
    | pricing decisions can be made from real data.
    |
    */

    'commission_rate' => env('MARKETPLACE_COMMISSION_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Return Trip Matching
    |--------------------------------------------------------------------------
    */

    'return_trip' => [
        'radius_m' => env('MARKETPLACE_RETURN_TRIP_RADIUS_M', 50000),
        'window_hours' => env('MARKETPLACE_RETURN_TRIP_WINDOW_HOURS', 72),
    ],
];
