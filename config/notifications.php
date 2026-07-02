<?php

return [
    'push' => [
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', true),
        'vapid' => [
            'subject' => env('VAPID_SUBJECT', 'mailto:admin@guardops.test'),
            'public_key' => env('VAPID_PUBLIC_KEY'),
            'private_key' => env('VAPID_PRIVATE_KEY'),
        ],
    ],

    'sms' => [
        'enabled' => env('SMS_NOTIFICATIONS_ENABLED', false),
        'driver' => env('SMS_DRIVER', 'twilio'),
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        'templates_requiring_sms' => [
            'sos.raised',
            'patrol.missed',
            'geofence.violation',
            'guard.idle',
        ],
    ],

    'idle_alert_minutes' => (int) env('GUARD_IDLE_ALERT_MINUTES', 15),
    'geofence_check_on_location' => true,
];
