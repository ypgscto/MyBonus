<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'kirimi' => [
        'api_url' => env('KIRIMI_API_URL', 'https://api.kirimi.id/v1/send-message'),
        'user_code' => env('KIRIMI_USER_CODE','KMX7RZ0426'),
        'secret' => env('KIRIMI_SECRET','41269fcef358bde6998262e09b5b80ceb631c6833517b2fefcfdf97b2357c2bd'),
        'device_id' => env('KIRIMI_DEVICE_ID','D-M0CSD')
    ],

];
