<?php

namespace Database\Seeders;

use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\NotificationTemplate;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\SitePost;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-security'],
            [
                'name' => 'Demo Security Company',
                'status' => 'active',
                'subdomain' => 'demo-security',
                'domain' => null,
            ]
        );
        $plan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'monthly_price' => 499,
                'annual_price' => 4990,
                'max_guards' => 1000,
                'max_sites' => 500,
                'features' => [
                    'guards', 'schedules', 'attendance', 'incidents', 'reports',
                    'patrols', 'gps', 'dispatch', 'equipment', 'visitors',
                    'clients', 'client_portal', 'billing', 'payroll',
                    'compliance', 'analytics', 'marketplace',
                ],
                'status' => 'active',
            ]
        );
        SubscriptionPlan::firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'monthly_price' => 99,
                'annual_price' => 990,
                'max_guards' => 25,
                'max_sites' => 10,
                'features' => ['guards', 'schedules', 'patrols', 'gps', 'incidents', 'clients'],
                'status' => 'active',
            ]
        );
        TenantSubscription::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['subscription_plan_id' => $plan->id, 'status' => 'trial', 'trial_ends_at' => now()->addDays(14)]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            ['tenant_id' => $tenant->id, 'name' => 'Demo Admin', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $admin->assignRole('company-admin');

        $platformAdmin = User::firstOrCreate(
            ['email' => 'platform@guardops.test'],
            ['tenant_id' => null, 'name' => 'Platform Admin', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $platformAdmin->assignRole('super-admin');

        $guardUser = User::firstOrCreate(
            ['email' => 'john.guard@test'],
            ['tenant_id' => $tenant->id, 'name' => 'John Mensah', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $guardUser->assignRole('guard');

        $client = ClientAccount::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Gold Mine Ltd'],
            ['industry' => 'Mining', 'email' => 'security@goldmine.test', 'phone' => '000-000', 'status' => 'active', 'default_hourly_rate' => 25]
        );
        $site = Site::firstOrCreate(
            ['tenant_id' => $tenant->id, 'client_account_id' => $client->id, 'name' => 'Main Gate'],
            ['address' => 'Obuasi', 'latitude' => 6.206, 'longitude' => -1.665, 'geofence_radius_meters' => 250, 'status' => 'active']
        );
        $post = SitePost::firstOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'name' => 'Gatehouse A'],
            ['status' => 'active']
        );
        $guard = Guard::updateOrCreate(
            ['tenant_id' => $tenant->id, 'employee_number' => 'G-001'],
            [
                'user_id' => $guardUser->id,
                'first_name' => 'John',
                'last_name' => 'Mensah',
                'phone' => '0240000000',
                'email' => 'john.guard@test',
                'status' => 'active',
                'hourly_rate' => 10,
                'license_number' => 'SEC-001',
                'rank' => 'Senior Officer',
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verified_by_user_id' => $admin->id,
                'show_current_assignment' => true,
            ]
        );

        \App\Models\GuardVerificationToken::updateOrCreate(
            ['guard_id' => $guard->id, 'token' => 'DEMOVERIFY01'],
            ['tenant_id' => $tenant->id, 'revoked_at' => null, 'expires_at' => now()->addYear()]
        );

        \App\Models\TenantSetting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'id_card'],
            ['value' => [
                'tagline' => 'Stay connected. Stay protected.',
                'brand_color' => '#8C1D2F',
                'phone' => '+233 302 770 0205',
                'phone_secondary' => '0205 965 133',
                'email' => 'info@demosecurity.test',
                'website' => 'www.demosecurity.test',
                'address' => 'Ringway Estates, Accra, Ghana',
            ]]
        );

        $route = PatrolRoute::firstOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'name' => 'Night Round'],
            ['expected_duration_minutes' => 45, 'status' => 'active']
        );
        foreach ([['Gate QR', 'GATE-QR-001', 1], ['Warehouse QR', 'WARE-QR-001', 2], ['Fence QR', 'FENCE-QR-001', 3]] as $cp) {
            PatrolCheckpoint::firstOrCreate(
                ['tenant_id' => $tenant->id, 'patrol_route_id' => $route->id, 'code' => $cp[1]],
                ['name' => $cp[0], 'sequence' => $cp[2], 'status' => 'active']
            );
        }
        Shift::firstOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'title' => 'Day Shift'],
            [
                'client_account_id' => $client->id,
                'site_post_id' => $post->id,
                'starts_at' => now()->setHour(8),
                'ends_at' => now()->setHour(18),
                'required_guards' => 2,
                'billing_rate' => 25,
                'billable_hours' => 10,
                'status' => 'open',
            ]
        );

        $shift = Shift::where('tenant_id', $tenant->id)->where('title', 'Day Shift')->first();
        ShiftAssignment::firstOrCreate(
            ['tenant_id' => $tenant->id, 'shift_id' => $shift->id, 'guard_id' => $guard->id],
            ['status' => 'assigned']
        );

        foreach ([
            ['incident.submitted', 'New incident: {{title}}', 'An incident ({{severity}}) was submitted and needs review.'],
            ['sos.raised', 'SOS ALERT', '{{message}} — respond immediately in the control room.'],
            ['compliance.expiring', 'Compliance expiry notice', '{{count}} certifications/documents expire soon for {{tenant}}.'],
            ['patrol.missed', 'Missed patrol alert', '{{count}} patrol session(s) were marked missed for {{tenant}}.'],
        ] as [$code, $subject, $body]) {
            NotificationTemplate::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $code, 'channel' => 'email'],
                ['subject' => $subject, 'body' => $body, 'is_active' => true]
            );
        }
    }
}
