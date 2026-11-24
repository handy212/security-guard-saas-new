<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_alerts', function (Blueprint $table): void {
            $table->timestamp('backup_escalate_at')->nullable();
            $table->timestamp('backup_escalated_at')->nullable();
            $table->timestamp('backup_escalation_failed_at')->nullable();
            $table->index(['acknowledged_at', 'resolved_at', 'backup_escalated_at', 'backup_escalate_at'], 'operational_alerts_backup_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('operational_alerts', function (Blueprint $table): void {
            $table->dropIndex('operational_alerts_backup_due_index');
            $table->dropColumn(['backup_escalate_at', 'backup_escalated_at', 'backup_escalation_failed_at']);
        });
    }
};
