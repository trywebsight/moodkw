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

    'tap' => [
        'secret_key' => env('TAP_SECRET_KEY'),
        'public_key' => env('TAP_PUBLIC_KEY'),
        'mode' => env('TAP_MODE', 'test'),
    ],

    'respond' => [
        'base_url' => env('RESPOND_BASE_URL', 'https://api.respond.io/v2'),
        'channel_id' => env('RESPOND_CHANNEL_ID'),
        'channel_api_token' => env('RESPOND_CHANNEL_API_TOKEN'),
        'payment_template' => env('RESPOND_PAYMENT_TEMPLATE'),
        'payment_template_language' => env('RESPOND_PAYMENT_TEMPLATE_LANGUAGE', 'ar'),
    ],

];
