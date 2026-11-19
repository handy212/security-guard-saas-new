<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('billing_period')->nullable()->after('client_account_id');
            $table->unique(['tenant_id', 'client_account_id', 'billing_period'], 'invoices_tenant_client_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_tenant_client_period_unique');
            $table->dropColumn('billing_period');
        });
    }
};
