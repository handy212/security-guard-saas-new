<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patriot_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('instance', 64);
            $table->string('account_reference', 160);
            $table->string('account_number', 160)->nullable();
            $table->string('billing_reference', 160)->nullable();
            $table->string('site_reference', 160)->nullable();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('verification_status')->default('pending');
            $table->timestamp('synchronized_at')->nullable();
            $table->text('response_details')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'instance', 'account_reference'], 'patriot_account_identity');
            $table->index(['tenant_id', 'instance', 'billing_reference'], 'patriot_billing_identity');
            $table->index(['tenant_id', 'instance', 'site_reference'], 'patriot_site_identity');
        });

        foreach (['client_accounts', 'sites'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('imported_from_patriot')->default(false);
            });
        }

        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->string('patriot_instance', 64)->nullable();
            $table->string('patriot_sync_status')->nullable();
            $table->json('patriot_intake_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_events', fn (Blueprint $table) => $table->dropColumn(['patriot_instance', 'patriot_sync_status', 'patriot_intake_snapshot']));
        foreach (['client_accounts', 'sites'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('imported_from_patriot'));
        }
        Schema::dropIfExists('patriot_account_mappings');
    }
};
