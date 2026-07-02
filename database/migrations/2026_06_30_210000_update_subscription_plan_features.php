<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $enterpriseFeatures = [
            'guards', 'schedules', 'attendance', 'incidents', 'reports',
            'patrols', 'gps', 'dispatch', 'equipment', 'visitors',
            'clients', 'client_portal', 'billing', 'payroll',
            'compliance', 'analytics', 'marketplace',
            'custom_reports', 'messenger', 'estimates', 'workforce', 'passdown', 'sms_alerts', 'webhooks', 'api',
        ];

        $professionalFeatures = [
            'guards', 'schedules', 'attendance', 'incidents', 'reports',
            'patrols', 'gps', 'dispatch', 'clients', 'client_portal',
        ];

        $businessFeatures = [
            'guards', 'schedules', 'attendance', 'incidents', 'reports',
            'patrols', 'gps', 'dispatch', 'equipment', 'visitors',
            'clients', 'client_portal', 'billing', 'payroll', 'compliance',
            'analytics', 'marketplace', 'messenger', 'custom_reports', 'estimates', 'sms_alerts',
        ];

        SubscriptionPlan::where('slug', 'enterprise')->update(['features' => $enterpriseFeatures]);
        SubscriptionPlan::where('slug', 'starter')->update(['features' => ['guards', 'schedules', 'attendance', 'incidents', 'patrols', 'gps', 'clients']]);

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'monthly_price' => 199,
                'annual_price' => 1990,
                'max_guards' => 100,
                'max_sites' => 50,
                'features' => $professionalFeatures,
                'status' => 'active',
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'business'],
            [
                'name' => 'Business',
                'monthly_price' => 299,
                'annual_price' => 2990,
                'max_guards' => 250,
                'max_sites' => 100,
                'features' => $businessFeatures,
                'status' => 'active',
            ]
        );
    }

    public function down(): void
    {
        // No rollback — plan features are data, not schema.
    }
};
