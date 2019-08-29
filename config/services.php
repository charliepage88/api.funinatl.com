<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    's3' => [
        'cdn_url' => env('ASSETS_S3_URL'),
        'cdn_bucket' => env('ASSETS_S3_BUCKET')
    ],

    'circleci' => [
        'token' => env('CIRCLE_CI_TOKEN')
    ],

    'algolia' => [
        'places' => [
            'key' => env('ALGOLIA_PLACES_KEY'),
            'app_id' => env('ALGOLIA_PLACES_APP_ID')
        ]
    ],

    'webhooks' => [
        'sync' => [
            'token' => env('WEBHOOK_SYNC_TOKEN'),
            'url'   => env('WEBHOOK_SYNC_URL')
        ]
    ],

    'spotify' => [
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'secret'    => env('SPOTIFY_CLIENT_SECRET')
    ],

    'oauth' => [
        'client_id' => env('OAUTH_CLIENT_ID'),
        'client_secret' => env('OAUTH_CLIENT_SECRET')
    ],

    'graphql' => [
        'endpoint' => env('GRAPHQL_ENDPOINT')
    ],
];
