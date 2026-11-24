<?php

return [
    'operational_alerts_enabled' => env('OPERATIONAL_ALERTS_ENABLED', false),

    'operational_events' => [
        'sos.raised' => ['severity' => 'critical', 'priority' => 100],
        'patriot.intake' => ['severity' => 'critical', 'priority' => 90],
        'dispatch.critical' => ['severity' => 'critical', 'priority' => 80],
        'incident.critical' => ['severity' => 'critical', 'priority' => 80],
        'coverage.unprotected' => ['severity' => 'critical', 'priority' => 85],
        'coverage.understaffed' => ['severity' => 'high', 'priority' => 60],
    ],

    'routine_severities' => [
        'alarm.overdue' => 'warning',
        'dispatch.guard_declined' => 'high',
        'patriot.connection_unavailable' => 'warning',
        'patriot.connection_restored' => 'info',
        'patriot.writeback_failed' => 'high',
        'patriot.account_sync_failed' => 'warning',
        'coverage.restored' => 'info',
        'geofence.violation' => 'warning',
        'guard.idle' => 'warning',
        'patrol.missed' => 'warning',
        'dispatch.assigned' => 'high',
    ],

    'acknowledgment_seconds' => (int) env('OPERATIONAL_ALERT_ACK_SECONDS', 120),

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
