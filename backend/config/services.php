<?php

return [
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'twilio' => [
        'sid'            => env('TWILIO_SID'),
        'token'          => env('TWILIO_TOKEN'),
        'whatsapp_from'  => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
    ],

    'meta_wa' => [
        'token'    => env('META_WA_TOKEN'),
        'phone_id' => env('META_WA_PHONE_ID'),
        'version'  => env('META_WA_VERSION', 'v18.0'),
    ],

    'africastalking' => [
        'username'  => env('AT_USERNAME', 'sandbox'),
        'api_key'   => env('AT_API_KEY'),
        'sender_id' => env('AT_SENDER_ID', 'NUVO'),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'),
    ],

    'flutterwave' => [
        'public_key'   => env('FLW_PUBLIC_KEY'),
        'secret_key'   => env('FLW_SECRET_KEY'),
        'webhook_hash' => env('FLW_WEBHOOK_HASH'),
    ],
];
