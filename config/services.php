<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have a
    | conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
        'studio_key' => env('RESEND_STUDIO_KEY', env('RESEND_KEY')),
        'studio_from_address' => env('PITMETRIC_STUDIO_FROM_ADDRESS', 'hello@pitmetric.it'),
        'studio_from_name' => env('PITMETRIC_STUDIO_FROM_NAME', 'Simone | PitMetric'),
        'studio_reply_to' => env('PITMETRIC_STUDIO_REPLY_TO', 'outreach@reply.pitmetric.it'),
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

];
