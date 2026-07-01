<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shift_assignments')) {
            return;
        }

        Schema::table('shift_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('shift_assignments', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('shift_assignments', 'notes')) {
                $table->text('notes')->nullable()->after('confirmed_at');
            }
        });

        if (Schema::hasColumn('shift_assignments', 'assigned_at')) {
            DB::table('shift_assignments')
                ->whereNull('assigned_at')
                ->update(['assigned_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shift_assignments')) {
            return;
        }

        Schema::table('shift_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('shift_assignments', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
            if (Schema::hasColumn('shift_assignments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
