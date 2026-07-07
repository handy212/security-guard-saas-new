<?php

return [
    'document_types' => [
        'sop' => 'Standard operating procedure',
        'permit' => 'Permit / license',
        'insurance' => 'Insurance',
        'floor_plan' => 'Floor plan / map',
        'general' => 'General',
    ],

    'report_types' => [
        'daily_activity' => 'Daily activity report',
        'patrol_summary' => 'Patrol summary',
        'incidents' => 'Incident digest',
        'custom' => 'Custom report',
    ],

    'report_frequencies' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ],

    'default_settings' => [
        'require_geofence_clock_in' => true,
        'notify_on_incident' => true,
        'patrol_reminder_minutes' => 30,
        'show_in_client_portal' => true,
    ],
];
