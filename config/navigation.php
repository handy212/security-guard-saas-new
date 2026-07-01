<?php

return [
    'navigation' => [
        'primary' => [
            ['href' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'permission' => 'dashboard.view'],
            ['href' => '/guard', 'label' => 'Field app', 'icon' => 'mobile', 'permission' => 'mobile.use', 'feature' => 'guards', 'highlight' => true],
            ['href' => '/settings', 'label' => 'Settings', 'icon' => 'settings', 'permission' => 'settings.manage'],
        ],
        'groups' => [
            'Operations' => [
                ['href' => '/dispatch', 'label' => 'Dispatch', 'icon' => 'dispatch', 'permission' => 'dispatch.manage', 'feature' => 'dispatch'],
                ['href' => '/schedules', 'label' => 'Schedules', 'icon' => 'schedules', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
                ['href' => '/schedules/calendar', 'label' => 'Calendar', 'icon' => 'calendar', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
                ['href' => '/attendance/timekeeping', 'label' => 'Attendance', 'icon' => 'attendance', 'permission' => 'attendance.manage', 'feature' => 'attendance'],
                ['href' => '/patrols', 'label' => 'Patrols', 'icon' => 'patrols', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
                ['href' => '/incidents', 'label' => 'Incidents', 'icon' => 'incidents', 'permission' => 'incidents.manage', 'feature' => 'incidents'],
                ['href' => '/reports/daily', 'label' => 'Daily Reports', 'icon' => 'reports', 'permission' => 'reports.approve', 'feature' => 'reports'],
            ],
            'People' => [
                ['href' => '/guards', 'label' => 'Guards', 'icon' => 'guards', 'permission' => 'guards.manage', 'feature' => 'guards'],
                ['href' => '/guards/know-your-guard', 'label' => 'Know Your Guard', 'icon' => 'verify', 'permission' => 'guards.manage', 'feature' => 'guards'],
                ['href' => '/equipment', 'label' => 'Equipment', 'icon' => 'equipment', 'permission' => 'equipment.manage', 'feature' => 'equipment'],
                ['href' => '/visitors', 'label' => 'Visitors', 'icon' => 'visitors', 'permission' => 'visitors.manage', 'feature' => 'visitors'],
            ],
            'Clients' => [
                ['href' => '/clients', 'label' => 'Clients', 'icon' => 'clients', 'permission' => 'clients.manage', 'feature' => 'clients'],
                ['href' => '/sites', 'label' => 'Sites', 'icon' => 'sites', 'permission' => 'sites.manage', 'feature' => 'clients'],
                ['href' => '/clients/complaints', 'label' => 'Complaints', 'icon' => 'complaints', 'permission' => 'clients.manage', 'feature' => 'clients'],
            ],
            'Finance' => [
                ['href' => '/billing/invoices', 'label' => 'Invoices', 'icon' => 'billing', 'permission' => 'billing.manage', 'feature' => 'billing'],
                ['href' => '/billing/payroll', 'label' => 'Payroll', 'icon' => 'payroll', 'permission' => 'payroll.manage', 'feature' => 'payroll'],
                ['href' => '/billing/subscription', 'label' => 'Subscription', 'icon' => 'subscription', 'permission' => 'billing.manage'],
            ],
            'Compliance' => [
                ['href' => '/compliance', 'label' => 'Overview', 'icon' => 'compliance', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
                ['href' => '/compliance/policies', 'label' => 'Policies', 'icon' => 'policies', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
                ['href' => '/sites/compliance', 'label' => 'Site SLA', 'icon' => 'sla', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
            ],
            'Insights' => [
                ['href' => '/analytics', 'label' => 'Analytics', 'icon' => 'chart', 'permission' => 'analytics.view', 'feature' => 'analytics'],
                ['href' => '/schedules/deployment-sheet', 'label' => 'Deployment Sheet', 'icon' => 'deployment', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
                ['href' => '/schedules/marketplace', 'label' => 'Shift Marketplace', 'icon' => 'marketplace', 'permission' => 'schedules.manage', 'feature' => 'marketplace'],
                ['href' => '/patrols/playback', 'label' => 'Patrol Playback', 'icon' => 'playback', 'permission' => 'patrols.manage', 'feature' => 'gps'],
                ['href' => '/patrols/vehicles', 'label' => 'Vehicle Patrols', 'icon' => 'vehicle', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
            ],
        ],
    ],

    'settings' => [
        ['href' => '/settings/roles', 'label' => 'Roles & Permissions', 'icon' => 'roles', 'permission' => 'settings.manage'],
        ['href' => '/settings/audit-log', 'label' => 'Audit trail', 'icon' => 'audit', 'permission' => 'settings.manage'],
        ['href' => '/settings/team', 'label' => 'Team passwords', 'icon' => 'team', 'permission' => 'settings.manage'],
        ['href' => '/settings/two-factor', 'label' => 'Two-Factor Auth', 'icon' => 'security', 'permission' => null],
        ['href' => '/settings/webhooks', 'label' => 'Webhooks', 'icon' => 'webhooks', 'permission' => 'settings.manage'],
        ['href' => '/mobile/offline-sync', 'label' => 'Offline Sync', 'icon' => 'offline', 'permission' => 'mobile.use'],
    ],

    'platform' => [
        ['href' => '/saas/tenants', 'label' => 'Tenants', 'icon' => 'tenants', 'permission' => 'tenants.manage'],
        ['href' => '/saas/plans', 'label' => 'Plans', 'icon' => 'plan', 'permission' => 'tenants.manage'],
        ['href' => '/saas/subscriptions', 'label' => 'Subscriptions', 'icon' => 'subscription', 'permission' => 'tenants.manage'],
    ],
];
