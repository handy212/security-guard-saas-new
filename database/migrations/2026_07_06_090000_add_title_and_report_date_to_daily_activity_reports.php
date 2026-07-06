<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_activity_reports')) {
            return;
        }

        Schema::table('daily_activity_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_activity_reports', 'title')) {
                $table->string('title')->nullable()->after('guard_id');
            }

            if (! Schema::hasColumn('daily_activity_reports', 'report_date')) {
                $table->date('report_date')->nullable()->after('title');
            }
        });

        DB::table('daily_activity_reports')
            ->whereNull('report_date')
            ->update([
                'report_date' => DB::raw('DATE(COALESCE(submitted_at, created_at))'),
            ]);

        DB::table('daily_activity_reports')
            ->whereNull('title')
            ->update([
                'title' => 'Daily activity report',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_activity_reports')) {
            return;
        }

        Schema::table('daily_activity_reports', function (Blueprint $table) {
            if (Schema::hasColumn('daily_activity_reports', 'report_date')) {
                $table->dropColumn('report_date');
            }

            if (Schema::hasColumn('daily_activity_reports', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
