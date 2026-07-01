<?php

return [
    'navigation' => [
        'primary' => [
            ['href' => '/dashboard', 'label' => 'Dashboard', 'permission' => 'dashboard.view'],
            ['href' => '/settings', 'label' => 'Settings', 'permission' => 'settings.manage'],
        ],
        'groups' => [
            'Operations' => [
                ['href' => '/dispatch', 'label' => 'Dispatch', 'permission' => 'dispatch.manage', 'feature' => 'dispatch'],
                ['href' => '/schedules', 'label' => 'Schedules', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
                ['href' => '/schedules/calendar', 'label' => 'Calendar', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
                ['href' => '/attendance/timekeeping', 'label' => 'Attendance', 'permission' => 'attendance.manage', 'feature' => 'attendance'],
                ['href' => '/patrols', 'label' => 'Patrols', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
                ['href' => '/incidents', 'label' => 'Incidents', 'permission' => 'incidents.manage', 'feature' => 'incidents'],
                ['href' => '/reports/daily', 'label' => 'Daily Reports', 'permission' => 'reports.approve', 'feature' => 'reports'],
            ],
            'People' => [
                ['href' => '/guards', 'label' => 'Guards', 'permission' => 'guards.manage', 'feature' => 'guards'],
                ['href' => '/guards/know-your-guard', 'label' => 'Know Your Guard', 'permission' => 'guards.manage', 'feature' => 'guards'],
                ['href' => '/guard', 'label' => 'Field app', 'permission' => 'mobile.use', 'feature' => 'guards'],
                ['href' => '/equipment', 'label' => 'Equipment', 'permission' => 'equipment.manage', 'feature' => 'equipment'],
                ['href' => '/visitors', 'label' => 'Visitors', 'permission' => 'visitors.manage', 'feature' => 'visitors'],
            ],
            'Clients' => [
                ['href' => '/clients', 'label' => 'Clients', 'permission' => 'clients.manage', 'feature' => 'clients'],
                ['href' => '/sites', 'label' => 'Sites', 'permission' => 'sites.manage', 'feature' => 'clients'],
                ['href' => '/clients/complaints', 'label' => 'Complaints', 'permission' => 'clients.manage', 'feature' => 'clients'],
            ],
            'Finance' => [
                ['href' => '/billing/invoices', 'label' => 'Invoices', 'permission' => 'billing.manage', 'feature' => 'billing'],
                ['href' => '/billing/payroll', 'label' => 'Payroll', 'permission' => 'payroll.manage', 'feature' => 'payroll'],
                ['href' => '/billing/subscription', 'label' => 'Subscription', 'permission' => 'billing.manage'],
            ],
            'Compliance' => [
                ['href' => '/compliance', 'label' => 'Overview', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
                ['href' => '/compliance/policies', 'label' => 'Policies', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
                ['href' => '/sites/compliance', 'label' => 'Site SLA', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
            ],
            'Insights' => [
                ['href' => '/analytics', 'label' => 'Analytics', 'permission' => 'analytics.view', 'feature' => 'analytics'],
                ['href' => '/schedules/deployment-sheet', 'label' => 'Deployment Sheet', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
                ['href' => '/schedules/marketplace', 'label' => 'Shift Marketplace', 'permission' => 'schedules.manage', 'feature' => 'marketplace'],
                ['href' => '/patrols/playback', 'label' => 'Patrol Playback', 'permission' => 'patrols.manage', 'feature' => 'gps'],
                ['href' => '/patrols/vehicles', 'label' => 'Vehicle Patrols', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
            ],
        ],
    ],

    'settings' => [
        ['href' => '/settings/roles', 'label' => 'Roles & Permissions', 'permission' => 'settings.manage'],
        ['href' => '/settings/audit-log', 'label' => 'Audit trail', 'permission' => 'view audit trail'],
        ['href' => '/settings/team', 'label' => 'Team passwords', 'permission' => 'settings.manage'],
        ['href' => '/settings/two-factor', 'label' => 'Two-Factor Auth', 'permission' => null],
        ['href' => '/settings/webhooks', 'label' => 'Webhooks', 'permission' => 'settings.manage'],
        ['href' => '/mobile/offline-sync', 'label' => 'Offline Sync', 'permission' => 'mobile.use'],
    ],

    'platform' => [
        ['href' => '/saas/tenants', 'label' => 'Tenants', 'permission' => 'tenants.manage'],
        ['href' => '/saas/plans', 'label' => 'Plans', 'permission' => 'tenants.manage'],
        ['href' => '/saas/subscriptions', 'label' => 'Subscriptions', 'permission' => 'tenants.manage'],
    ],
];
