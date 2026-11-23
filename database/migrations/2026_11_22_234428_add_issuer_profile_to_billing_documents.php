<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices', 'estimates'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->json('issuer_profile')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['invoices', 'estimates'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('issuer_profile');
            });
        }
    }
};
