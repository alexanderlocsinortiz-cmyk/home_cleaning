<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'paymongo' => [
        'api_url' => env('PAYMONGO_API_URL', 'https://api.paymongo.com'),
        'ca_bundle' => env('PAYMONGO_CA_BUNDLE', storage_path('app/cacert.pem')),
        'disable_proxy' => env('PAYMONGO_DISABLE_PROXY', true),
        'timeout_seconds' => (int) env('PAYMONGO_TIMEOUT_SECONDS', 10),
        'connect_timeout_seconds' => (int) env('PAYMONGO_CONNECT_TIMEOUT_SECONDS', 5),
        'retry_times' => (int) env('PAYMONGO_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('PAYMONGO_RETRY_SLEEP_MS', 250),
        'maya_method_type' => env('PAYMONGO_MAYA_METHOD_TYPE', 'paymaya'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'checkout_redirect_enabled' => env('PAYMONGO_CHECKOUT_REDIRECT_ENABLED', true),
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'daily' => [
        'api_url' => env('DAILY_API_URL', 'https://api.daily.co/v1'),
        'api_key' => env('DAILY_API_KEY'),
        'domain' => env('DAILY_DOMAIN'),
        'ca_bundle' => env('DAILY_CA_BUNDLE', storage_path('app/cacert.pem')),
        'disable_proxy' => env('DAILY_DISABLE_PROXY', true),
        'room_ttl_hours' => (int) env('DAILY_ROOM_TTL_HOURS', 6),
        'token_ttl_minutes' => (int) env('DAILY_TOKEN_TTL_MINUTES', 120),
    ],

];
