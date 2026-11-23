<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscription_plans')->whereJsonLength('features', 0)->update(['features' => null]);
    }

    public function down(): void
    {
        // Legacy null and empty selections both used the default features.
    }
};
