<?php

return [
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
    'patriot' => [
        'url' => env('PATRIOT_URL'),
        'tenant' => env('PATRIOT_TENANT'),
        'polling_enabled' => env('PATRIOT_POLLING_ENABLED', false),
        'writeback_enabled' => env('PATRIOT_WRITEBACK_ENABLED', false),
        'writeback_test_job_id' => env('PATRIOT_WRITEBACK_TEST_JOB_ID'),
        'username' => env('PATRIOT_USERNAME'),
        'password' => env('PATRIOT_PASSWORD'),
    ],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
];
