<?php

/**
 * Default tenant-scoped roles provisioned for each security company.
 * Platform-only role: super-admin (tenant_id = null).
 */
return [
    'roles' => [
        'company-admin' => [
            'dashboard.view', 'clients.manage', 'sites.manage', 'guards.manage', 'schedules.manage',
            'attendance.manage', 'patrols.manage', 'incidents.manage', 'reports.approve', 'dispatch.manage',
            'billing.manage', 'payroll.manage', 'settings.manage', 'audit.view', 'client_portal.view', 'mobile.use',
            'analytics.view', 'compliance.manage', 'equipment.manage', 'visitors.manage', 'exports.manage',
        ],
        'operations-manager' => [
            'dashboard.view', 'clients.manage', 'sites.manage', 'guards.manage', 'schedules.manage',
            'attendance.manage', 'patrols.manage', 'incidents.manage', 'reports.approve', 'dispatch.manage',
            'analytics.view', 'compliance.manage', 'equipment.manage', 'visitors.manage', 'audit.view',
        ],
        'supervisor' => [
            'dashboard.view', 'attendance.manage', 'patrols.manage', 'incidents.manage',
            'reports.approve', 'dispatch.manage', 'audit.view',
        ],
        'guard' => ['mobile.use'],
        'client' => ['client_portal.view'],
        'finance' => ['dashboard.view', 'billing.manage', 'payroll.manage', 'exports.manage', 'analytics.view'],
    ],

    'platform_roles' => [
        'super-admin',
    ],
];
