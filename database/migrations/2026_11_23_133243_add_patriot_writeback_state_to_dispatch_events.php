<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->string('patriot_response_state', 20)->nullable();
            $table->string('patriot_pending_action', 20)->nullable();
            $table->timestamp('patriot_writeback_started_at')->nullable();
            $table->string('patriot_writeback_error')->nullable();
            $table->index(['tenant_id', 'patriot_pending_action']);
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'patriot_pending_action']);
            $table->dropColumn(['patriot_response_state', 'patriot_pending_action', 'patriot_writeback_started_at', 'patriot_writeback_error']);
        });
    }
};
