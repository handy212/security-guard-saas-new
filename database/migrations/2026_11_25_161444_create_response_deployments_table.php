<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('response_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dispatch_team_id')->constrained();
            $table->foreignId('standby_point_id')->nullable()->constrained('dispatch_standby_points')->nullOnDelete();
            $table->json('standby_location');
            $table->json('officer_ids');
            $table->foreignId('custodian_id')->constrained('guards');
            $table->string('handover_location');
            $table->foreignId('deployed_by')->constrained('users');
            $table->foreignId('arrived_by')->nullable()->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('planned_end_at');
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('arrival_override')->nullable();
            $table->index(['tenant_id', 'closed_at']);
            $table->timestamps();
        });
        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->foreignId('response_deployment_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->foreignId('response_deployment_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatch_events', fn (Blueprint $table) => $table->dropConstrainedForeignId('response_deployment_id'));
        Schema::table('equipment_assignments', fn (Blueprint $table) => $table->dropConstrainedForeignId('response_deployment_id'));
        Schema::dropIfExists('response_deployments');
    }
};
