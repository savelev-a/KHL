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
    'khl' => [
        'base_url' => env('KHL_API_BASE_URL', 'https://khl.api.webcaster.pro/api/khl_mobile'),
        'timeout'  => (int) env('KHL_API_TIMEOUT', 15),
        'retries'  => (int) env('KHL_API_RETRIES', 3),
        'playoff_spots'  => (int) env('KHL_PLAYOFF_SPOTS', 8),  // ← сколько мест от конференции
        'ot_probability'   => (float) env('KHL_OT_PROBABILITY', 0.25),
    ],

];
