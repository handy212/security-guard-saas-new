<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscription_plans')
            ->whereIn('slug', ['business', 'enterprise'])
            ->get(['id', 'features'])
            ->each(function (object $plan): void {
                $features = collect(json_decode((string) $plan->features, true) ?: [])
                    ->push('expenses')
                    ->unique()
                    ->values()
                    ->all();
                DB::table('subscription_plans')->where('id', $plan->id)->update([
                    'features' => json_encode($features),
                ]);
            });
    }

    public function down(): void
    {
        // Feature access is additive and should not be removed on rollback.
    }
};
