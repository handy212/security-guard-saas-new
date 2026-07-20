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

    'patrols' => [
        ['href' => '/patrols', 'label' => 'Patrol board', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
        ['href' => '/patrols/fleet', 'label' => 'Fleet', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
        ['href' => '/patrols/vehicles', 'label' => 'Vehicle patrols', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
        ['href' => '/patrols/playback', 'label' => 'Patrol playback', 'permission' => 'patrols.manage', 'feature' => 'gps'],
        ['href' => '/passdown', 'label' => 'Passdown', 'permission' => 'patrols.manage', 'feature' => 'passdown'],
    ],

    'reports' => [
        ['href' => '/reports', 'label' => 'Overview', 'permission' => 'reports.approve', 'feature' => 'reports'],
        ['href' => '/reports/daily', 'label' => 'Daily reports', 'permission' => 'reports.approve', 'feature' => 'reports'],
        ['href' => '/reports/templates', 'label' => 'Custom templates', 'permission' => 'reports.approve', 'feature' => 'custom_reports'],
    ],

    /*
     | Primary scheduler hub (~6). Secondary tools live under groups.schedules_more
     | and remain routed, but stay out of the flyout / schedules sub-nav.
     */
    'schedules' => [
        ['href' => '/schedules/calendar', 'label' => 'Calendar', 'group' => 'Planning', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules', 'label' => 'Day roster', 'group' => 'Planning', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/templates', 'label' => 'Templates', 'group' => 'Planning', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/deploy', 'label' => 'Deploy', 'group' => 'Planning', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/attendance', 'label' => 'Attendance', 'group' => 'Field day', 'permission' => 'attendance.manage', 'feature' => 'attendance'],
        ['href' => '/schedules/reconciliation', 'label' => 'Reconciliation', 'group' => 'Field day', 'permission' => 'attendance.manage', 'feature' => 'attendance'],
    ],

    'schedules_more' => [
        ['href' => '/schedules/deployment-sheet', 'label' => 'Deployment sheet', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/shift-status', 'label' => 'Confirmations', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/schedules/open-shifts', 'label' => 'Open shifts', 'permission' => 'schedules.manage', 'feature' => 'marketplace'],
        ['href' => '/schedules/shift-exchange', 'label' => 'Shift exchange', 'permission' => 'schedules.manage', 'feature' => 'marketplace'],
        ['href' => '/schedules/time-off', 'label' => 'Time off', 'permission' => 'schedules.manage', 'feature' => 'workforce'],
    ],

    'billing' => [
        ['href' => '/billing', 'label' => 'Overview'],
        ['href' => '/billing/invoices', 'label' => 'Invoices', 'permission' => 'billing.manage', 'feature' => 'billing'],
        ['href' => '/billing/estimates', 'label' => 'Estimates', 'permission' => 'billing.manage', 'feature' => 'estimates'],
        ['href' => '/billing/payments', 'label' => 'Payments', 'permission' => 'billing.manage', 'feature' => 'billing'],
        ['href' => '/billing/expenses', 'label' => 'Expenses', 'permission' => 'billing.manage', 'feature' => 'expenses'],
        ['href' => '/billing/payroll', 'label' => 'Payroll', 'permission' => 'payroll.manage', 'feature' => 'payroll'],
        ['href' => '/compliance', 'label' => 'Compliance', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
        ['href' => '/compliance/policies', 'label' => 'Policies', 'permission' => 'compliance.manage', 'feature' => 'compliance'],
        ['href' => '/analytics', 'label' => 'Analytics', 'permission' => 'analytics.view', 'feature' => 'analytics'],
    ],

    /*
     | Slim ops spine (~9). Hubs and secondary tools live in groups.
     */
    'pinned' => [
        ['href' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'permission' => 'dashboard.view'],
        ['href' => '/tracking', 'label' => 'Live Tracker', 'icon' => 'gps', 'permission' => 'dispatch.manage', 'feature' => 'gps'],
        ['href' => '/clients', 'label' => 'Clients', 'icon' => 'clients', 'permission' => 'clients.manage', 'feature' => 'clients'],
        ['href' => '/sites', 'label' => 'Sites', 'icon' => 'sites', 'permission' => 'sites.manage', 'feature' => 'clients'],
        ['href' => '/guards', 'label' => 'Guards', 'icon' => 'guards', 'permission' => 'guards.manage', 'feature' => 'guards'],
        ['href' => '/schedules', 'label' => 'Scheduler', 'icon' => 'schedules', 'permission' => 'schedules.manage', 'feature' => 'schedules'],
        ['href' => '/dispatch', 'label' => 'Dispatch', 'icon' => 'dispatch', 'permission' => 'dispatch.manage', 'feature' => 'dispatch'],
        ['href' => '/incidents', 'label' => 'Incidents', 'icon' => 'incidents', 'permission' => 'incidents.manage', 'feature' => 'incidents'],
        ['href' => '/patrols', 'label' => 'Patrols', 'icon' => 'patrols', 'permission' => 'patrols.manage', 'feature' => 'patrols'],
        ['href' => '/billing', 'label' => 'Back Office', 'icon' => 'billing', 'hub' => 'billing'],
        ['href' => '/guard', 'label' => 'Field app', 'icon' => 'mobile', 'permission' => 'mobile.use', 'feature' => 'guards', 'highlight' => true],
    ],

    /*
     | Secondary accordion (~3 groups). Schedule extras live under Scheduler flyout
     | via schedules_more — do not duplicate them here.
     */
    'groups' => [
        'Workforce' => [
            ['href' => '/guards/know-your-guard', 'label' => 'Know Your Guard', 'icon' => 'guards', 'permission' => 'guards.manage', 'feature' => 'guards'],
            ['href' => '/guards/applications', 'label' => 'Applications', 'icon' => 'workforce', 'permission' => 'guards.manage', 'feature' => 'guards'],
            ['href' => '/visitors', 'label' => 'Visitors', 'icon' => 'visitors', 'permission' => 'visitors.manage', 'feature' => 'visitors'],
            ['href' => '/clients/complaints', 'label' => 'Complaints', 'icon' => 'clients', 'permission' => 'clients.manage', 'feature' => 'clients'],
        ],
        'Live ops' => [
            ['href' => '/messenger', 'label' => 'Messenger', 'icon' => 'messenger', 'permission' => 'dispatch.manage', 'feature' => 'messenger'],
        ],
        'Resources' => [
            ['href' => '/reports', 'label' => 'Reports', 'icon' => 'reports', 'permission' => 'reports.approve', 'feature' => 'reports'],
            ['href' => '/assets', 'label' => 'Assets', 'icon' => 'equipment', 'permission' => 'equipment.manage', 'feature' => 'equipment'],
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
        ['href' => '/billing/subscription', 'label' => 'Your plan', 'permission' => 'billing.manage'],
        ['href' => '/settings/branches', 'label' => 'Branches', 'permission' => 'settings.manage'],
        ['href' => '/settings/id-card', 'label' => 'ID Card', 'permission' => 'settings.manage'],
        ['href' => '/settings/know-your-guard', 'label' => 'KYG public page', 'permission' => 'settings.manage'],
        ['href' => '/settings/roles', 'label' => 'Roles & Permissions', 'permission' => 'settings.manage'],
        ['href' => '/settings/staff', 'label' => 'Team members', 'permission' => 'settings.manage'],
        ['href' => '/settings/audit-log', 'label' => 'Audit trail', 'permission' => 'audit.view'],
        ['href' => '/settings/team', 'label' => 'Team passwords', 'permission' => 'settings.manage'],
        ['href' => '/settings/two-factor', 'label' => 'Two-Factor Auth', 'permission' => null],
        ['href' => '/settings/webhooks', 'label' => 'Webhooks', 'permission' => 'settings.manage', 'feature' => 'webhooks'],
        ['href' => '/settings/notifications', 'label' => 'Notification templates', 'permission' => 'settings.manage'],
        ['href' => '/mobile/offline-sync', 'label' => 'Offline Sync', 'permission' => 'mobile.use'],
    ],
];
