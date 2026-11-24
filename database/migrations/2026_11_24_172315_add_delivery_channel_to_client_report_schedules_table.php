<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_report_schedules', function (Blueprint $table): void {
            $table->string('delivery_channel', 16)->default('email');
            $table->string('whatsapp_phone', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_report_schedules', function (Blueprint $table): void {
            $table->dropColumn(['delivery_channel', 'whatsapp_phone']);
        });
    }
};
