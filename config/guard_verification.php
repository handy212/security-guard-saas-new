<?php

return [
    /** Days until issued QR tokens expire (per-tenant override via tenant_settings.verification). */
    'token_ttl_days' => (int) env('GUARD_VERIFICATION_TOKEN_TTL_DAYS', 365),

    'page' => [
        'subtitle' => 'Real-time staff verification.',
        'verified_banner_title' => 'Verified & authorised today',
        'verified_banner_hint' => 'Safe to grant access after matching photo, ID card and uniform.',
        'access_guidance' => 'Grant access only if the person matches the photo, presents a company ID card, and is wearing the approved uniform. Do not accept screenshots.',
        'security_notice' => 'Always scan the guard\'s ID card directly. Do not rely on forwarded links or screenshots. If anything looks suspicious, contact the control room immediately.',
        'verified_by_label' => 'Verified by Control Room',
        'database_source_label' => 'Source: secure staff database',
        'live_page_notice' => 'This is a live verification page. Details update when the card is scanned.',
        'expected_appearance' => [
            'Branded uniform',
            'Visible staff ID',
            'Company radio',
            'Bodycam / guard tour device',
        ],
        'competencies_heading' => 'Verified competencies',
        'appearance_heading' => 'Expected appearance',
        'support_heading' => '24/7 verification support',
        'support_intro' => 'If you are unsure about this guard, contact the control room before granting access.',
        'call_button_label' => 'Call control room',
        'report_button_label' => 'Report concern',
    ],
];
