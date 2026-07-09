<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Route Prefix & Version
    |--------------------------------------------------------------------------
    |
    | API_PREFIX: route prefix (default "api" → /api/v1/...)
    | API_VERSION: version segment for route grouping
    |
    */

    'prefix' => env('API_PREFIX', 'api'),

    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Used in API responses (e.g. file download URLs). Defaults to APP_URL.
    | Set API_BASE_URL in production when the API is served from a different
    | domain than the web app (e.g. api.bonusku.example.com).
    |
    */

    'base_url' => env('API_BASE_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (requests per minute)
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'login' => (int) env('API_RATE_LIMIT_LOGIN', 10),
        'authenticated' => (int) env('API_RATE_LIMIT_AUTH', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => (int) env('API_PER_PAGE', 15),
        'max_per_page' => (int) env('API_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Authentication (Sanctum)
    |--------------------------------------------------------------------------
    |
    | API_TOKEN_EXPIRATION: minutes until token expires. null = never expires
    | (convenient for local dev). Set e.g. 10080 (7 days) in production.
    |
    | API_DEFAULT_DEVICE_NAME: fallback device label when client omits device_name
    |
    | API_TOKEN_ABILITIES: comma-separated Sanctum token abilities
    |
    */

    'token' => [
        'expiration_minutes' => filled(env('API_TOKEN_EXPIRATION'))
            ? (int) env('API_TOKEN_EXPIRATION')
            : null,
        'default_device_name' => env('API_DEFAULT_DEVICE_NAME', 'mobile'),
        'abilities' => array_filter(array_map('trim', explode(',', env('API_TOKEN_ABILITIES', 'mobile-access')))),
    ],

];
