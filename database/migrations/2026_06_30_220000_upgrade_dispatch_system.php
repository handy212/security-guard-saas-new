<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dispatch_events')) {
            Schema::table('dispatch_events', function (Blueprint $table) {
                if (! Schema::hasColumn('dispatch_events', 'dispatch_number')) {
                    $table->string('dispatch_number')->nullable()->after('id');
                }
                if (! Schema::hasColumn('dispatch_events', 'client_account_id')) {
                    $table->foreignId('client_account_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('dispatch_events', 'created_by_user_id')) {
                    $table->foreignId('created_by_user_id')->nullable()->after('guard_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('dispatch_events', 'caller_type')) {
                    $table->string('caller_type')->default('client')->after('priority');
                }
                if (! Schema::hasColumn('dispatch_events', 'caller_name')) {
                    $table->string('caller_name')->nullable()->after('caller_type');
                }
                if (! Schema::hasColumn('dispatch_events', 'incident_location')) {
                    $table->string('incident_location')->nullable()->after('caller_name');
                }
                if (! Schema::hasColumn('dispatch_events', 'incident_date')) {
                    $table->date('incident_date')->nullable()->after('incident_location');
                }
                if (! Schema::hasColumn('dispatch_events', 'incident_time')) {
                    $table->string('incident_time', 8)->nullable()->after('incident_date');
                }
                if (! Schema::hasColumn('dispatch_events', 'action_taken')) {
                    $table->text('action_taken')->nullable()->after('description');
                }
                if (! Schema::hasColumn('dispatch_events', 'internal_notes')) {
                    $table->text('internal_notes')->nullable()->after('action_taken');
                }
                if (! Schema::hasColumn('dispatch_events', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('internal_notes');
                }
                if (! Schema::hasColumn('dispatch_events', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('attachment_path');
                }
                if (! Schema::hasColumn('dispatch_events', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
                if (! Schema::hasColumn('dispatch_events', 'sos_alert_id')) {
                    $table->foreignId('sos_alert_id')->nullable()->after('longitude')->constrained('sos_alerts')->nullOnDelete();
                }
                if (! Schema::hasColumn('dispatch_events', 'incident_id')) {
                    $table->foreignId('incident_id')->nullable()->after('sos_alert_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('dispatch_events', 'assigned_at')) {
                    $table->timestamp('assigned_at')->nullable()->after('opened_at');
                }
                if (! Schema::hasColumn('dispatch_events', 'en_route_at')) {
                    $table->timestamp('en_route_at')->nullable()->after('assigned_at');
                }
                if (! Schema::hasColumn('dispatch_events', 'on_scene_at')) {
                    $table->timestamp('on_scene_at')->nullable()->after('en_route_at');
                }
                if (! Schema::hasColumn('dispatch_events', 'resolved_at')) {
                    $table->timestamp('resolved_at')->nullable()->after('on_scene_at');
                }
            });
        }

        if (! Schema::hasTable('dispatch_activity_logs')) {
            Schema::create('dispatch_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('dispatch_event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_activity_logs');

        if (Schema::hasTable('dispatch_events')) {
            Schema::table('dispatch_events', function (Blueprint $table) {
                foreach ([
                    'dispatch_number', 'client_account_id', 'created_by_user_id',
                    'caller_type', 'caller_name', 'incident_location', 'incident_date', 'incident_time',
                    'action_taken', 'internal_notes', 'attachment_path',
                    'latitude', 'longitude', 'sos_alert_id', 'incident_id',
                    'assigned_at', 'en_route_at', 'on_scene_at', 'resolved_at',
                ] as $column) {
                    if (Schema::hasColumn('dispatch_events', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
