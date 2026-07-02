<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('endpoint')->unique();
                $table->string('public_key')->nullable();
                $table->string('auth_token')->nullable();
                $table->string('content_encoding')->default('aesgcm');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('geofence_violations')) {
            Schema::create('geofence_violations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedInteger('distance_meters')->default(0);
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('guard_idle_alerts')) {
            Schema::create('guard_idle_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
                $table->timestamp('last_location_at');
                $table->unsignedInteger('idle_minutes')->default(0);
                $table->timestamp('alerted_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'resolved_at']);
            });
        }

        if (! Schema::hasTable('report_templates')) {
            Schema::create('report_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_account_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_template_fields')) {
            Schema::create('report_template_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_template_id')->constrained()->cascadeOnDelete();
                $table->string('label');
                $table->string('field_type');
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->json('options')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_template_assignments')) {
            Schema::create('report_template_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('report_template_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_post_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
                $table->unique(['report_template_id', 'site_id', 'site_post_id'], 'report_template_site_post_unique');
            });
        }

        if (! Schema::hasTable('custom_report_submissions')) {
            Schema::create('custom_report_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('report_template_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shift_assignment_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('draft');
                $table->json('data')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (! Schema::hasTable('shift_templates')) {
            Schema::create('shift_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shift_template_items')) {
            Schema::create('shift_template_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shift_template_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('required_guards')->default(1);
                $table->decimal('billing_rate', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shift_confirmations')) {
            Schema::create('shift_confirmations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shift_assignment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
                $table->unique(['shift_assignment_id', 'guard_id']);
            });
        }

        if (! Schema::hasTable('estimates')) {
            Schema::create('estimates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
                $table->string('estimate_number');
                $table->date('estimate_date');
                $table->date('valid_until')->nullable();
                $table->string('status')->default('draft');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->foreignId('converted_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('estimate_items')) {
            Schema::create('estimate_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
                $table->string('description');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->boolean('is_taxable')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash');
                $table->timestamp('paid_at');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_exports')) {
            Schema::create('payroll_exports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('provider')->default('quickbooks');
                $table->date('period_start');
                $table->date('period_end');
                $table->string('file_path')->nullable();
                $table->foreignId('exported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('exported_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('message_threads')) {
            Schema::create('message_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->string('subject');
                $table->string('type')->default('site');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('message_thread_participants')) {
            Schema::create('message_thread_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_thread_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['message_thread_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_thread_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('body');
                $table->string('attachment_path')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('passdown_logs')) {
            Schema::create('passdown_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_post_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shift_assignment_id')->nullable()->constrained()->nullOnDelete();
                $table->text('content');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('attendance_logs')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('attendance_logs', 'reconciled_at')) {
                    $table->timestamp('reconciled_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('attendance_logs', 'reconciled_by_user_id')) {
                    $table->foreignId('reconciled_by_user_id')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('attendance_logs', 'reconciliation_notes')) {
                    $table->text('reconciliation_notes')->nullable()->after('reconciled_by_user_id');
                }
                if (! Schema::hasColumn('attendance_logs', 'original_status')) {
                    $table->string('original_status')->nullable()->after('reconciliation_notes');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'amount_paid')) {
                    $table->decimal('amount_paid', 12, 2)->default(0)->after('grand_total');
                }
            });
        }

        if (Schema::hasTable('patrol_sessions')) {
            Schema::table('patrol_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('patrol_sessions', 'completion_percent')) {
                    $table->unsignedTinyInteger('completion_percent')->default(0)->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patrol_sessions') && Schema::hasColumn('patrol_sessions', 'completion_percent')) {
            Schema::table('patrol_sessions', fn (Blueprint $table) => $table->dropColumn('completion_percent'));
        }
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'amount_paid')) {
            Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('amount_paid'));
        }
        if (Schema::hasTable('attendance_logs')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                foreach (['reconciled_at', 'reconciled_by_user_id', 'reconciliation_notes', 'original_status'] as $col) {
                    if (Schema::hasColumn('attendance_logs', $col)) {
                        if ($col === 'reconciled_by_user_id') {
                            $table->dropConstrainedForeignId($col);
                        } else {
                            $table->dropColumn($col);
                        }
                    }
                }
            });
        }

        Schema::dropIfExists('passdown_logs');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_thread_participants');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('payroll_exports');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('estimate_items');
        Schema::dropIfExists('estimates');
        Schema::dropIfExists('shift_confirmations');
        Schema::dropIfExists('shift_template_items');
        Schema::dropIfExists('shift_templates');
        Schema::dropIfExists('custom_report_submissions');
        Schema::dropIfExists('report_template_assignments');
        Schema::dropIfExists('report_template_fields');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('guard_idle_alerts');
        Schema::dropIfExists('geofence_violations');
        Schema::dropIfExists('push_subscriptions');
    }
};
