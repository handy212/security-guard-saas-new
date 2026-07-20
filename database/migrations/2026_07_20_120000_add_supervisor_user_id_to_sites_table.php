<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'supervisor_user_id')) {
                $table->foreignId('supervisor_user_id')
                    ->nullable()
                    ->after('client_account_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'supervisor_user_id')) {
                $table->dropConstrainedForeignId('supervisor_user_id');
            }
        });
    }
};
