<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnterprisePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view sites','assign guards','approve reports','manage payroll','view client portal','export reports',
            'manage tenants','manage subscription plans','manage branches','manage tenant settings','manage billing limits',
            'manage clients','manage client contacts','manage site documents','manage emergency contacts','manage sla requirements',
            'manage guard profiles','manage guard documents','manage guard certifications','manage uniforms equipment','manage training records','manage disciplinary records','manage availability leave',
            'create shifts','approve shift swaps','approve open shift bids','view calendar','export deployment sheet',
            'clock attendance','manage attendance exceptions','track breaks','generate timesheets',
            'manage patrol routes','scan qr checkpoints','scan nfc checkpoints','view patrol playback','manage vehicle patrols',
            'manage incidents','notify clients','approve incidents','export incident pdf',
            'manage daily activity reports','auto send reports','manage dispatch','use live map','trigger sos','assign incidents','receive realtime alerts',
            'raise complaints','approve client reports','manage compliance','manage retention policies','manage escalation rules','view audit trail',
            'generate invoices','calculate overtime','manage allowances deductions','export accounting',
            'view analytics','view guard performance','view revenue analytics'
        ];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
