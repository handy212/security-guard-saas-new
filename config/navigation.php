<?php

return [
    'assets' => [
        ['href' => '/assets', 'label' => 'Overview', 'permission' => 'equipment.manage'],
        ['href' => '/assets/list', 'label' => 'Assets', 'permission' => 'equipment.manage'],
        ['href' => '/assets/categories', 'label' => 'Categories', 'permission' => 'equipment.manage'],
        ['href' => '/assets/inventory', 'label' => 'Asset Inventory', 'permission' => 'equipment.manage'],
        ['href' => '/assets/vendors', 'label' => 'Vendors', 'permission' => 'equipment.manage'],
        ['href' => '/assets/purchase-orders', 'label' => 'Purchase Orders', 'permission' => 'equipment.manage'],
    ],

    'schedules' => [
        ['href' => '/schedules', 'label' => 'Schedule', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/calendar', 'label' => 'Calendar', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/templates', 'label' => 'Shift Templates', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/attendance', 'label' => 'Attendance', 'permission' => 'attendance.manage', 'feature' => 'attendance'],
        ['href' => '/schedules/shift-status', 'label' => 'Shift Status', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/open-shifts', 'label' => 'Open Shifts', 'permission' => 'schedules.manage', 'feature' => 'marketplace'],
        ['href' => '/schedules/shift-exchange', 'label' => 'Shift Exchange', 'permission' => 'schedules.manage', 'feature' => 'marketplace'],
        ['href' => '/schedules/time-off', 'label' => 'Time Off', 'permission' => 'schedules.manage', 'feature' => 'workforce'],
        ['href' => '/schedules/deployment-sheet', 'label' => 'Deployment', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
    ],

    'pinned' => [
        ['href' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'permission' => 'dashboard.view'],
        ['href' => '/guard', 'label' => 'Field app', 'icon' => 'mobile', 'permission' => 'mobile.use', 'feature' => 'guards', 'highlight' => true],
        ['href' => '/dispatch', 'label' => 'Dispatch', 'icon' => 'dispatch', 'permission' => 'dispatch.manage', 'feature' => 'dispatch'],
        ['href' => '/schedules', 'label' => 'Schedule', 'icon' => 'schedules', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/incidents', 'label' => 'Incidents', 'icon' => 'incidents', 'permission' => 'incidents.manage', 'feature' => 'incidents'],
        ['href' => '/tracking', 'label' => 'Live Tracker', 'icon' => 'gps', 'permission' => 'dispatch.manage', 'feature' => 'gps'],
        ['href' => '/messenger', 'label' => 'Messenger', 'icon' => 'messenger', 'permission' => 'dispatch.manage', 'feature' => 'messenger'],
        ['href' => '/assets', 'label' => 'Assets', 'icon' => 'equipment', 'permission' => 'equipment.manage', 'feature' => 'equipment'],
    ],

    'groups' => [
        'Schedule' => [
            ['href' => '/schedules/calendar', 'label' => 'Calendar', 'icon' => 'schedules', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
            ['href' => '/schedules/deployment-sheet', 'label' => 'Deployment Sheet', 'icon' => 'schedules', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
            ['href' => '/attendance/reconciliation', 'label' => 'Reconciliation', 'icon' => 'attendance', 'permission' => 'attendance.manage', 'feature' => 'attendance'],
        ],
        'Patrols & Reports' => [
            ['href' => '/patrols', 'label' => 'Patrols', 'icon' => 'patrols', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
            ['href' => '/passdown', 'label' => 'Passdown', 'icon' => 'reports', 'permission' => 'patrols.manage', 'feature' => 'passdown'],
            ['href' => '/patrols/playback', 'label' => 'Patrol Playback', 'icon' => 'gps', 'permission' => 'patrols.manage', 'feature' => 'gps'],
            ['href' => '/patrols/vehicles', 'label' => 'Vehicle Patrols', 'icon' => 'patrols', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
            ['href' => '/reports/daily', 'label' => 'Daily Reports', 'icon' => 'reports', 'permission' => 'reports.approve', 'feature' => 'reports'],
            ['href' => '/reports/templates', 'label' => 'Custom Reports', 'icon' => 'reports', 'permission' => 'reports.approve', 'feature' => 'custom_reports'],
        ],
        'People' => [
            ['href' => '/guards', 'label' => 'Guards', 'icon' => 'guards', 'permission' => 'guards.manage', 'feature' => 'guards'],
            ['href' => '/guards/know-your-guard', 'label' => 'Know Your Guard', 'icon' => 'guards', 'permission' => 'guards.manage', 'feature' => 'guards'],
            ['href' => '/visitors', 'label' => 'Visitors', 'icon' => 'visitors', 'permission' => 'visitors.manage', 'feature' => 'visitors'],
        ],
        'Clients' => [
            ['href' => '/clients', 'label' => 'Clients', 'icon' => 'clients', 'permission' => 'clients.manage', 'feature' => 'clients'],
            ['href' => '/sites', 'label' => 'Sites', 'icon' => 'sites', 'permission' => 'sites.manage', 'feature' => 'clients'],
            ['href' => '/clients/complaints', 'label' => 'Complaints', 'icon' => 'clients', 'permission' => 'clients.manage', 'feature' => 'clients'],
        ],
        'Finance' => [
            ['href' => '/billing/invoices', 'label' => 'Invoices', 'icon' => 'billing', 'permission' => 'billing.manage', 'feature' => 'billing'],
            ['href' => '/billing/estimates', 'label' => 'Estimates', 'icon' => 'billing', 'permission' => 'billing.manage', 'feature' => 'estimates'],
            ['href' => '/billing/payroll', 'label' => 'Payroll', 'icon' => 'billing', 'permission' => 'payroll.manage', 'feature' => 'payroll'],
            ['href' => '/billing/subscription', 'label' => 'Subscription', 'icon' => 'billing', 'permission' => 'billing.manage'],
        ],
        'Compliance & Insights' => [
            ['href' => '/compliance', 'label' => 'Overview', 'icon' => 'compliance', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
            ['href' => '/compliance/policies', 'label' => 'Policies', 'icon' => 'compliance', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
            ['href' => '/sites/compliance', 'label' => 'Site SLA', 'icon' => 'sites', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
            ['href' => '/analytics', 'label' => 'Analytics', 'icon' => 'analytics', 'permission' => 'analytics.view', 'feature' => 'analytics'],
        ],
    ],

    'footer' => [
        ['href' => '/settings', 'label' => 'Settings', 'icon' => 'settings', 'permission' => 'settings.manage'],
    ],

    'platform' => [
        ['href' => '/saas/tenants', 'label' => 'Tenants', 'icon' => 'tenants', 'permission' => 'tenants.manage'],
        ['href' => '/saas/plans', 'label' => 'Plans', 'icon' => 'plans', 'permission' => 'tenants.manage'],
        ['href' => '/saas/subscriptions', 'label' => 'Subscriptions', 'icon' => 'subscriptions', 'permission' => 'tenants.manage'],
    ],

    'settings' => [
        ['href' => '/settings/id-card', 'label' => 'ID Card', 'permission' => 'settings.manage'],
        ['href' => '/settings/roles', 'label' => 'Roles & Permissions', 'permission' => 'settings.manage'],
        ['href' => '/settings/audit-log', 'label' => 'Audit trail', 'permission' => 'audit.view'],
        ['href' => '/settings/team', 'label' => 'Team passwords', 'permission' => 'settings.manage'],
        ['href' => '/settings/two-factor', 'label' => 'Two-Factor Auth', 'permission' => null],
        ['href' => '/settings/webhooks', 'label' => 'Webhooks', 'permission' => 'settings.manage', 'feature' => 'webhooks'],
        ['href' => '/mobile/offline-sync', 'label' => 'Offline Sync', 'permission' => 'mobile.use'],
    ],
];
