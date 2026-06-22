<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->reconcileClientAccounts();
        $this->reconcileSites();
        $this->reconcileGuards();
        $this->reconcileShifts();
        $this->reconcileAttendanceLogs();
        $this->reconcileIncidents();
        $this->reconcileInvoices();
        $this->reconcileInvoiceItems();
        $this->reconcileTimesheets();
        $this->reconcileSosAlerts();
        $this->reconcilePatrolRoutes();
        $this->reconcilePatrolCheckpoints();
        $this->reconcilePatrolSessions();
        $this->reconcileCheckpointScans();
        $this->reconcileDailyActivityReports();
    }

    private function reconcileClientAccounts(): void
    {
        if (! Schema::hasTable('client_accounts')) {
            return;
        }

        Schema::table('client_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('client_accounts', 'industry')) {
                $table->string('industry')->nullable();
            }
            if (! Schema::hasColumn('client_accounts', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('client_accounts', 'default_hourly_rate')) {
                $table->decimal('default_hourly_rate', 10, 2)->default(0);
            }
        });
    }

    private function reconcileSites(): void
    {
        if (! Schema::hasTable('sites')) {
            return;
        }

        if (Schema::hasColumn('sites', 'geofence_radius') && ! Schema::hasColumn('sites', 'geofence_radius_meters')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->unsignedInteger('geofence_radius_meters')->default(150);
            });
            DB::table('sites')->update([
                'geofence_radius_meters' => DB::raw('geofence_radius'),
            ]);
        } elseif (! Schema::hasColumn('sites', 'geofence_radius_meters')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->unsignedInteger('geofence_radius_meters')->default(150);
            });
        }

        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'instructions')) {
                $table->text('instructions')->nullable();
            }
        });
    }

    private function reconcileGuards(): void
    {
        if (! Schema::hasTable('guards')) {
            return;
        }

        if (Schema::hasColumn('guards', 'employee_no') && ! Schema::hasColumn('guards', 'employee_number')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->string('employee_number')->nullable();
            });
            DB::table('guards')->update([
                'employee_number' => DB::raw('employee_no'),
            ]);
        }

        Schema::table('guards', function (Blueprint $table) {
            if (! Schema::hasColumn('guards', 'employee_number')) {
                $table->string('employee_number')->nullable();
            }
            if (! Schema::hasColumn('guards', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('guards', 'license_number')) {
                $table->string('license_number')->nullable();
            }
            if (! Schema::hasColumn('guards', 'license_expires_at')) {
                $table->date('license_expires_at')->nullable();
            }
            if (! Schema::hasColumn('guards', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable();
            }
            if (! Schema::hasColumn('guards', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable();
            }
        });
    }

    private function reconcileShifts(): void
    {
        if (! Schema::hasTable('shifts')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'client_account_id')) {
                $table->foreignId('client_account_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('shifts', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('shifts', 'billing_rate')) {
                $table->decimal('billing_rate', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('shifts', 'billable_hours')) {
                $table->decimal('billable_hours', 8, 2)->default(8);
            }
            if (! Schema::hasColumn('shifts', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    private function reconcileAttendanceLogs(): void
    {
        if (! Schema::hasTable('attendance_logs')) {
            return;
        }

        Schema::table('attendance_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_logs', 'site_id')) {
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('attendance_logs', 'clock_in_at')) {
                $table->timestamp('clock_in_at')->nullable();
            }
            if (! Schema::hasColumn('attendance_logs', 'clock_out_at')) {
                $table->timestamp('clock_out_at')->nullable();
            }
            if (! Schema::hasColumn('attendance_logs', 'clock_in_latitude')) {
                $table->decimal('clock_in_latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('attendance_logs', 'clock_in_longitude')) {
                $table->decimal('clock_in_longitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('attendance_logs', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('attendance_logs', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('attendance_logs', 'geofence_validated')) {
                $table->boolean('geofence_validated')->default(false);
            }
            if (! Schema::hasColumn('attendance_logs', 'worked_minutes')) {
                $table->unsignedInteger('worked_minutes')->nullable();
            }
            if (! Schema::hasColumn('attendance_logs', 'status')) {
                $table->string('status')->default('on_time');
            }
        });
    }

    private function reconcileIncidents(): void
    {
        if (! Schema::hasTable('incidents')) {
            return;
        }

        if (Schema::hasColumn('incidents', 'incident_type') && ! Schema::hasColumn('incidents', 'type')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->string('type')->nullable();
            });
            DB::table('incidents')->update(['type' => DB::raw('incident_type')]);
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'type')) {
                $table->string('type')->nullable();
            }
            if (! Schema::hasColumn('incidents', 'reported_by_user_id')) {
                $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('incidents', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('incidents', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('incidents', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('incidents', 'reported_at')) {
                $table->timestamp('reported_at')->nullable();
            }
            if (! Schema::hasColumn('incidents', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (! Schema::hasColumn('incidents', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
            if (! Schema::hasColumn('incidents', 'resolution')) {
                $table->text('resolution')->nullable();
            }
        });
    }

    private function reconcileInvoices(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        if (Schema::hasColumn('invoices', 'invoice_no') && ! Schema::hasColumn('invoices', 'invoice_number')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('invoice_number')->nullable();
            });
            DB::table('invoices')->update(['invoice_number' => DB::raw('invoice_no')]);
        }

        if (Schema::hasColumn('invoices', 'issue_date') && ! Schema::hasColumn('invoices', 'invoice_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->date('invoice_date')->nullable();
            });
            DB::table('invoices')->update(['invoice_date' => DB::raw('issue_date')]);
        }

        if (Schema::hasColumn('invoices', 'tax') && ! Schema::hasColumn('invoices', 'tax_total')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('tax_total', 12, 2)->default(0);
            });
            DB::table('invoices')->update(['tax_total' => DB::raw('tax')]);
        }

        if (Schema::hasColumn('invoices', 'total') && ! Schema::hasColumn('invoices', 'grand_total')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('grand_total', 12, 2)->default(0);
            });
            DB::table('invoices')->update(['grand_total' => DB::raw('total')]);
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'invoice_date')) {
                $table->date('invoice_date')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'tax_total')) {
                $table->decimal('tax_total', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('invoices', 'grand_total')) {
                $table->decimal('grand_total', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('invoices', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });
    }

    private function reconcileInvoiceItems(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        if (Schema::hasColumn('invoice_items', 'total') && ! Schema::hasColumn('invoice_items', 'line_total')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->decimal('line_total', 12, 2)->default(0);
            });
            DB::table('invoice_items')->update(['line_total' => DB::raw('total')]);
        } elseif (! Schema::hasColumn('invoice_items', 'line_total')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->decimal('line_total', 12, 2)->default(0);
            });
        }
    }

    private function reconcileTimesheets(): void
    {
        if (! Schema::hasTable('timesheets')) {
            return;
        }

        Schema::table('timesheets', function (Blueprint $table) {
            if (! Schema::hasColumn('timesheets', 'gross_pay')) {
                $table->decimal('gross_pay', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('timesheets', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('timesheets', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });
    }

    private function reconcileSosAlerts(): void
    {
        if (! Schema::hasTable('sos_alerts')) {
            return;
        }

        if (Schema::hasColumn('sos_alerts', 'triggered_at') && ! Schema::hasColumn('sos_alerts', 'raised_at')) {
            Schema::table('sos_alerts', function (Blueprint $table) {
                $table->timestamp('raised_at')->nullable();
            });
            DB::table('sos_alerts')->update(['raised_at' => DB::raw('triggered_at')]);
        }

        Schema::table('sos_alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('sos_alerts', 'message')) {
                $table->string('message')->nullable();
            }
            if (! Schema::hasColumn('sos_alerts', 'raised_at')) {
                $table->timestamp('raised_at')->nullable();
            }
            if (! Schema::hasColumn('sos_alerts', 'acknowledged_by_user_id')) {
                $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('sos_alerts', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable();
            }
        });
    }

    private function reconcilePatrolRoutes(): void
    {
        if (! Schema::hasTable('patrol_routes')) {
            return;
        }

        Schema::table('patrol_routes', function (Blueprint $table) {
            if (! Schema::hasColumn('patrol_routes', 'expected_duration_minutes')) {
                $table->unsignedInteger('expected_duration_minutes')->default(30);
            }
        });
    }

    private function reconcilePatrolCheckpoints(): void
    {
        if (! Schema::hasTable('patrol_checkpoints')) {
            return;
        }

        if (Schema::hasColumn('patrol_checkpoints', 'scan_order') && ! Schema::hasColumn('patrol_checkpoints', 'sequence')) {
            Schema::table('patrol_checkpoints', function (Blueprint $table) {
                $table->unsignedInteger('sequence')->default(1);
            });
            DB::table('patrol_checkpoints')->update(['sequence' => DB::raw('scan_order')]);
        }

        Schema::table('patrol_checkpoints', function (Blueprint $table) {
            if (! Schema::hasColumn('patrol_checkpoints', 'sequence')) {
                $table->unsignedInteger('sequence')->default(1);
            }
            if (! Schema::hasColumn('patrol_checkpoints', 'instructions')) {
                $table->text('instructions')->nullable();
            }
            if (! Schema::hasColumn('patrol_checkpoints', 'status')) {
                $table->string('status')->default('active');
            }
        });
    }

    private function reconcilePatrolSessions(): void
    {
        if (! Schema::hasTable('patrol_sessions')) {
            return;
        }

        if (Schema::hasColumn('patrol_sessions', 'ended_at') && ! Schema::hasColumn('patrol_sessions', 'completed_at')) {
            Schema::table('patrol_sessions', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable();
            });
            DB::table('patrol_sessions')->update(['completed_at' => DB::raw('ended_at')]);
        }

        Schema::table('patrol_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('patrol_sessions', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
            if (! Schema::hasColumn('patrol_sessions', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    private function reconcileCheckpointScans(): void
    {
        if (! Schema::hasTable('checkpoint_scans')) {
            return;
        }

        Schema::table('checkpoint_scans', function (Blueprint $table) {
            if (! Schema::hasColumn('checkpoint_scans', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    private function reconcileDailyActivityReports(): void
    {
        if (! Schema::hasTable('daily_activity_reports')) {
            return;
        }

        Schema::table('daily_activity_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_activity_reports', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Reconciliation migration is intentionally not reversed.
    }
};
