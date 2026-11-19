<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenant_subscriptions')
            ->whereIn('status', ['active', 'trial'])
            ->orderBy('id')
            ->get(['tenant_id', 'subscription_plan_id'])
            ->each(function (object $subscription): void {
                $plan = DB::table('subscription_plans')->find($subscription->subscription_plan_id);
                if (! $plan) {
                    return;
                }

                DB::table('billing_limits')->updateOrInsert(
                    ['tenant_id' => $subscription->tenant_id],
                    [
                        'max_guards' => $plan->max_guards ?? 25,
                        'max_sites' => $plan->max_sites ?? 10,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            });
    }

    public function down(): void
    {
        // Limits remain aligned with the selected plan on rollback.
    }
};
