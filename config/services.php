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
    'image_cache_ttl_days' => env('IMAGE_URL_CACHE_DAYS', 7),

    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'image_queue' => env('RABBITMQ_QUEUE_IMAGE', 'image-download'),
        'pornstar_queue' => env('RABBITMQ_QUEUE_PORNSTAR', 'pornstar-events'),
    ],

    'api' => [
        'pornstar_endpoint' => env('API_SERVICE_URL') . '/pornstars',
    ],

    'feed' => [
        'pornstars_url' => env('PORNSTAR_FEED_URL'),
    ],

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

];
