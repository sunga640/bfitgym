<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZKBio API Endpoints
    |--------------------------------------------------------------------------
    |
    | This integration intentionally targets only API-based ZKBio control-plane
    | access. If these endpoints are not available on the installed edition,
    | the integration marks the connection as "unsupported".
    |
    */
    'endpoints' => [
        'auth' => '/api/v1/auth/login',
        'health' => '/api/v1/system/health',
        'devices' => '/api/v1/access/devices',
        'personnel_upsert' => '/api/v1/access/personnel/upsert',
        'access_sync' => '/api/v1/access/rights/sync',
        'events' => '/api/v1/access/events',
    ],

    'http' => [
        'retry_times' => 2,
        'retry_sleep_ms' => 300,
    ],
];

