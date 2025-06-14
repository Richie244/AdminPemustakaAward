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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'api' => [ // <--- TAMBAHKAN INI
        'base_url' => env('MAIN_API_BASE_URL'), // Ganti MAIN_API_BASE_URL dengan nama variabel .env Anda
        'key' => env('MAIN_API_KEY', null),
    ],
    
        'external_api' => [
        'base_url' => env('EXTERNAL_API_BASE_URL'),
        'key' => env('EXTERNAL_API_KEY'), // Akan null jika EXTERNAL_API_KEY kosong di .env
    ],

        'civitas_api' => [ // Kunci spesifik untuk API Civitas Anda
        'url' => env('CIVITAS_API_URL'), // Fallback jika .env tidak diset
        'key' => env('CIVITAS_API_KEY', null), // Jika API Anda memerlukan key
    ],
];
