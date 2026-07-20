<?php

return [
    /** Days until issued QR tokens expire (per-tenant override via tenant_settings.verification). */
    'token_ttl_days' => (int) env('GUARD_VERIFICATION_TOKEN_TTL_DAYS', 365),

    'page' => [
        'subtitle' => 'Real-time staff verification.',
        'verified_banner_title' => 'Verified & authorised today',
        'verified_banner_hint' => 'Safe to grant access after matching photo, ID card and uniform.',
        'unassigned_banner_title' => 'No site assignment',
        'unassigned_banner_hint' => 'Identity is verified, but this officer is not currently assigned to any site. Do not grant access until assignment is confirmed.',
        'access_guidance' => 'Grant access only if the person matches the photo, presents a company ID card, and is wearing the approved uniform. Do not accept screenshots.',
        'security_notice' => 'Always scan the guard\'s ID card directly. Do not rely on forwarded links or screenshots. If anything looks suspicious, contact the control room immediately.',
        'verified_by_label' => 'Verified by Control Room',
        'database_source_label' => 'Source: secure staff database',
        'live_page_notice' => 'Live page — values refresh each time this QR is scanned.',
        // Appearance standards only — issued kit comes from the active shift assignment.
        'expected_appearance' => [
            'Branded uniform',
            'Visible staff ID',
        ],
        'competencies_heading' => 'Verified competencies',
        'appearance_heading' => 'Expected appearance',
        'issued_kit_heading' => 'Issued for this shift',
        'support_heading' => 'Need to confirm?',
        'support_intro' => 'If you are unsure about this guard, contact the control room or the site supervisor before granting access.',
        'call_button_label' => 'Call control room',
        'report_button_label' => 'Report concern',
        'supervisor_heading' => 'Site supervisor',
        'supervisor_intro' => 'Contact the supervisor for this site if you need on-site confirmation.',
        'call_supervisor_label' => 'Call supervisor',
    ],
];
