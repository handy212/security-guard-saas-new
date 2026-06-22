<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('billing_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->integer('max_guards')->default(25);
            $table->integer('max_sites')->default(10);
            $table->integer('max_clients')->default(10);
            $table->bigInteger('storage_mb')->default(5120);
            $table->timestamps();
        });

        Schema::create('site_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->integer('priority')->default(1);
            $table->timestamps();
        });

        Schema::create('site_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type')->nullable();
            $table->string('file_path');
            $table->date('expires_on')->nullable();
            $table->boolean('client_visible')->default(false);
            $table->timestamps();
        });

        Schema::create('site_sla_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('metric');
            $table->string('target_value');
            $table->string('frequency')->default('daily');
            $table->integer('grace_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('guard_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->string('skill');
            $table->string('level')->nullable();
            $table->timestamps();
        });

        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->string('course_name');
            $table->string('provider')->nullable();
            $table->date('completed_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('status')->default('valid');
            $table->timestamps();
        });

        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->string('case_number')->nullable();
            $table->string('type')->default('warning');
            $table->text('description');
            $table->string('action_taken')->nullable();
            $table->date('occurred_on')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_guard_id')->constrained('guards')->cascadeOnDelete();
            $table->foreignId('replacement_guard_id')->nullable()->constrained('guards')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('open_shift_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['shift_id', 'guard_id']);
        });

        Schema::create('break_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_log_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('type')->default('meal');
            $table->timestamps();
        });

        Schema::create('patrol_playback_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrol_session_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('vehicle_patrols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrol_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_number');
            $table->string('driver_name')->nullable();
            $table->integer('start_odometer')->nullable();
            $table->integer('end_odometer')->nullable();
            $table->json('fuel_log')->nullable();
            $table->timestamps();
        });

        Schema::create('client_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('incident_escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('incident_type')->nullable();
            $table->string('severity');
            $table->integer('notify_after_minutes')->default(0);
            $table->json('notify_roles')->nullable();
            $table->boolean('notify_client')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('data_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('record_type');
            $table->integer('retention_days');
            $table->boolean('legal_hold')->default(false);
            $table->timestamps();
        });

        Schema::create('accounting_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('csv');
            $table->string('export_type')->default('invoice');
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->integer('active_guards')->default(0);
            $table->integer('active_sites')->default(0);
            $table->integer('missed_patrols')->default(0);
            $table->json('incidents_by_severity')->nullable();
            $table->integer('late_shifts')->default(0);
            $table->integer('no_show_shifts')->default(0);
            $table->decimal('patrol_completion_rate', 5, 2)->default(0);
            $table->decimal('client_sla_performance', 5, 2)->default(0);
            $table->decimal('revenue_total', 14, 2)->default(0);
            $table->json('guard_scores')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        foreach ([
            'analytics_snapshots','accounting_exports','data_retention_policies','incident_escalation_rules','client_complaints','vehicle_patrols','patrol_playback_points','break_logs','open_shift_bids','shift_swap_requests','disciplinary_records','training_records','guard_skills','site_sla_requirements','site_documents','site_emergency_contacts','billing_limits','tenant_settings','branches'
        ] as $table) { Schema::dropIfExists($table); }
    }
};
