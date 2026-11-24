<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable();
        });

        Schema::create('operational_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 80);
            $table->string('source_reference', 100);
            $table->string('severity', 12);
            $table->unsignedTinyInteger('priority')->default(0);
            $table->string('title');
            $table->text('instructions');
            $table->string('action_url');
            $table->json('context')->nullable();
            $table->timestamp('acknowledge_by')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('escalation_failed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'event_type', 'source_reference']);
            $table->index(['tenant_id', 'resolved_at', 'acknowledged_at', 'priority', 'created_at'], 'operational_alerts_queue_index');
            $table->index(['acknowledged_at', 'resolved_at', 'escalated_at', 'acknowledge_by'], 'operational_alerts_due_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_alerts');
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn('archived_at');
        });
    }
};
