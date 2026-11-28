<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dispatch_standby_points', function (Blueprint $table) {
            $table->unsignedInteger('radius_meters')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatch_standby_points', function (Blueprint $table) {
            $table->dropColumn('radius_meters');
        });
    }
};
