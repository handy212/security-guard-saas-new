<?php

return [
    'evolution' => [
        'tenant_id' => env('EVOLUTION_TENANT_ID'),
        'url' => env('EVOLUTION_API_URL'),
        'instance' => env('EVOLUTION_INSTANCE'),
        'key' => env('EVOLUTION_API_KEY'),
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
    'patriot' => [
        'url' => env('PATRIOT_URL'),
        'tenant' => env('PATRIOT_TENANT'),
        'instance' => env('PATRIOT_INSTANCE'),
        'enrichment_enabled' => env('PATRIOT_ENRICHMENT_ENABLED', true),
        'sync_response_contacts' => env('PATRIOT_SYNC_RESPONSE_CONTACTS', false),
        'group_by_billing_account' => env('PATRIOT_GROUP_BY_BILLING_ACCOUNT', false),
        'polling_enabled' => env('PATRIOT_POLLING_ENABLED', false),
        'writeback_enabled' => env('PATRIOT_WRITEBACK_ENABLED', false),
        'writeback_test_job_id' => env('PATRIOT_WRITEBACK_TEST_JOB_ID'),
        'username' => env('PATRIOT_USERNAME'),
        'password' => env('PATRIOT_PASSWORD'),
    ],
    'geocoding' => [
        'url' => env('GEOCODING_URL'),
        'user_agent' => env('GEOCODING_USER_AGENT', 'GuardCore Pro'),
    ],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
];
