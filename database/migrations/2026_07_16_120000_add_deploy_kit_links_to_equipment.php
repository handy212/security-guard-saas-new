<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->foreignId('shift_assignment_id')
                ->nullable()
                ->after('site_id')
                ->constrained('shift_assignments')
                ->nullOnDelete();
        });

        Schema::table('equipment_assets', function (Blueprint $table) {
            $table->foreignId('fleet_vehicle_id')
                ->nullable()
                ->after('site_id')
                ->constrained('fleet_vehicles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_assignment_id');
        });

        Schema::table('equipment_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fleet_vehicle_id');
        });
    }
};
