<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->unsignedBigInteger('patriot_job_id')->nullable();
            $table->string('patriot_client_ref', 160)->nullable();
            $table->unique(['tenant_id', 'patriot_job_id']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('patriot_client_ref', 160)->nullable();
            $table->unique(['tenant_id', 'patriot_client_ref']);
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'patriot_client_ref']);
            $table->dropColumn('patriot_client_ref');
        });

        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'patriot_job_id']);
            $table->dropColumn(['patriot_job_id', 'patriot_client_ref']);
        });
    }
};
