<?php

return [
    /*
     * |--------------------------------------------------------------------------
     * | Third Party Services
     * |--------------------------------------------------------------------------
     * |
     * | This file is for storing the credentials for third party services such
     * | as Mailgun, Postmark, AWS and more. This file provides the de facto
     * | location for this type of information, allowing packages to have
     * | a conventional file to locate the various service credentials.
     * |
     */
    'zatca' => [
        'base_url' => env('ZATCA_BASE_URL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation'),
        'client_id' => env('ZATCA_CLIENT_ID'),
        'client_secret' => env('ZATCA_CLIENT_SECRET'),
    ],
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_PHONE_NUMBER'),
        'messagingServiceSid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'WHATSAPP_FROM' => env('TWILIO_WHATSAPP_FROM'),
    ],
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    // 'google' => [
    //     'web_client_id' => env('GOOGLE_WEB_CLIENT_ID'),
    //     'mobile_client_id' => env('GOOGLE_MOBILE_CLIENT_ID'),
    //     'client_secret' => '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',
    //     'redirect' => 'https://wlmma.com/api/auth/google/callback',
    // ],
    'google' => [
        'web_client_id' => env('GOOGLE_WEB_CLIENT_ID'),
        'mobile_client_id' => env('GOOGLE_MOBILE_CLIENT_ID'),
    ],
    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),  // Will generate dynamically
        'redirect' => env('APPLE_REDIRECT_URI'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'key' => storage_path('keys/apple_private_key.p8'),  // Path to .p8 file
    ],
];
