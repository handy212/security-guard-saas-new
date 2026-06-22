<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('email')->nullable(); $table->string('phone')->nullable(); $table->string('role')->nullable(); $table->timestamps();
        });
        Schema::create('guard_documents', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->string('type'); $table->string('file_path'); $table->date('expires_at')->nullable(); $table->string('status')->default('valid'); $table->timestamps();
        });
        Schema::create('guard_certifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('issuer')->nullable(); $table->date('issued_at')->nullable(); $table->date('expires_at')->nullable(); $table->string('status')->default('valid'); $table->timestamps();
        });
        Schema::create('incident_media', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('file_path'); $table->string('media_type')->default('photo'); $table->string('caption')->nullable(); $table->timestamps();
        });
        Schema::create('dispatch_events', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('guard_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type'); $table->string('priority')->default('normal'); $table->string('status')->default('open'); $table->longText('description')->nullable(); $table->timestamp('opened_at')->nullable(); $table->timestamp('closed_at')->nullable(); $table->timestamps();
        });
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('guard_id')->constrained()->cascadeOnDelete(); $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude',10,7)->nullable(); $table->decimal('longitude',10,7)->nullable(); $table->string('status')->default('open'); $table->timestamp('triggered_at')->nullable(); $table->timestamp('resolved_at')->nullable(); $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no'); $table->string('status')->default('draft'); $table->date('issue_date')->nullable(); $table->date('due_date')->nullable(); $table->decimal('subtotal',12,2)->default(0); $table->decimal('tax',12,2)->default(0); $table->decimal('total',12,2)->default(0); $table->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description'); $table->decimal('quantity',10,2)->default(1); $table->decimal('unit_price',12,2)->default(0); $table->decimal('total',12,2)->default(0); $table->timestamps();
        });
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->date('period_start'); $table->date('period_end'); $table->decimal('regular_hours',8,2)->default(0); $table->decimal('overtime_hours',8,2)->default(0); $table->string('status')->default('draft'); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs','timesheets','invoice_items','invoices','sos_alerts','dispatch_events','incident_media','guard_certifications','guard_documents','client_contacts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
