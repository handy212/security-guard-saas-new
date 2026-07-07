<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->boolean('portal_enabled')->default(false)->after('status');
            $table->text('portal_welcome_message')->nullable()->after('portal_enabled');
        });

        Schema::create('client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });

        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type')->default('general');
            $table->string('file_path');
            $table->date('expires_on')->nullable();
            $table->boolean('client_visible')->default(false);
            $table->timestamps();
        });

        Schema::create('client_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->string('report_type');
            $table->string('frequency')->default('weekly');
            $table->json('recipients')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_report_schedules');
        Schema::dropIfExists('client_documents');
        Schema::dropIfExists('client_notes');

        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'portal_enabled', 'portal_welcome_message']);
        });
    }
};
