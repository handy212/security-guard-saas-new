<?php

return [
    'navigation' => [
        'Overview' => [
            ['href' => '/dashboard', 'label' => 'Dashboard', 'permission' => 'dashboard.view'],
            ['href' => '/analytics', 'label' => 'Analytics', 'permission' => 'analytics.view'],
            ['href' => '/dispatch', 'label' => 'Dispatch', 'permission' => 'dispatch.manage'],
        ],
        'Operations' => [
            ['href' => '/schedules', 'label' => 'Schedules', 'permission' => 'schedules.manage'],
            ['href' => '/schedules/calendar', 'label' => 'Calendar', 'permission' => 'schedules.manage'],
            ['href' => '/schedules/deployment-sheet', 'label' => 'Deployment', 'permission' => 'schedules.manage'],
            ['href' => '/attendance/timekeeping', 'label' => 'Attendance', 'permission' => 'attendance.manage'],
            ['href' => '/patrols', 'label' => 'Patrols', 'permission' => 'patrols.manage'],
            ['href' => '/patrols/playback', 'label' => 'Playback', 'permission' => 'patrols.manage'],
            ['href' => '/incidents', 'label' => 'Incidents', 'permission' => 'incidents.manage'],
            ['href' => '/reports/daily', 'label' => 'Daily Reports', 'permission' => 'reports.approve'],
        ],
        'People & Assets' => [
            ['href' => '/guards', 'label' => 'Guards', 'permission' => 'guards.manage'],
            ['href' => '/guards/hr-records', 'label' => 'Guard HR', 'permission' => 'guards.manage'],
            ['href' => '/equipment', 'label' => 'Equipment', 'permission' => 'equipment.manage'],
            ['href' => '/visitors', 'label' => 'Visitors', 'permission' => 'visitors.manage'],
        ],
        'Clients' => [
            ['href' => '/clients', 'label' => 'Clients', 'permission' => 'clients.manage'],
            ['href' => '/sites', 'label' => 'Sites', 'permission' => 'sites.manage'],
            ['href' => '/clients/complaints', 'label' => 'Complaints', 'permission' => 'clients.manage'],
            ['href' => '/client-portal', 'label' => 'Client Portal', 'permission' => 'client_portal.view'],
        ],
        'Finance' => [
            ['href' => '/billing/invoices', 'label' => 'Invoices', 'permission' => 'billing.manage'],
            ['href' => '/billing/payroll', 'label' => 'Payroll', 'permission' => 'payroll.manage'],
            ['href' => '/billing/subscription', 'label' => 'Subscription', 'permission' => 'billing.manage'],
        ],
        'Compliance' => [
            ['href' => '/compliance', 'label' => 'Compliance', 'permission' => 'compliance.manage'],
            ['href' => '/compliance/policies', 'label' => 'Policies', 'permission' => 'compliance.manage'],
            ['href' => '/sites/compliance', 'label' => 'Site SLA', 'permission' => 'compliance.manage'],
        ],
        'Platform' => [
            ['href' => '/saas/tenants', 'label' => 'SaaS Tenants', 'permission' => 'tenants.manage'],
            ['href' => '/settings/roles', 'label' => 'Roles', 'permission' => 'settings.manage'],
            ['href' => '/settings/two-factor', 'label' => 'Two-Factor Auth', 'permission' => null],
            ['href' => '/settings/webhooks', 'label' => 'Webhooks', 'permission' => 'settings.manage'],
            ['href' => '/mobile/offline-sync', 'label' => 'Offline Sync', 'permission' => 'mobile.use'],
            ['href' => '/guard', 'label' => 'Guard App', 'permission' => 'mobile.use'],
        ],
    ],
];
