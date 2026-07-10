<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('car'); // car, motor, van, other
            $table->string('plate_number');
            $table->string('name')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('status')->default('available'); // available, in_use, maintenance, retired
            $table->unsignedInteger('current_odometer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'plate_number']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::table('vehicle_patrols', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_patrols', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('tenant_id')->constrained('fleet_vehicles')->nullOnDelete();
            }
            if (! Schema::hasColumn('vehicle_patrols', 'guard_id')) {
                $table->foreignId('guard_id')->nullable()->after('vehicle_id')->constrained('guards')->nullOnDelete();
            }
            if (! Schema::hasColumn('vehicle_patrols', 'status')) {
                $table->string('status')->default('active')->after('end_odometer');
            }
            if (! Schema::hasColumn('vehicle_patrols', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('vehicle_patrols', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_patrols', function (Blueprint $table) {
            foreach (['vehicle_id', 'guard_id', 'status', 'started_at', 'ended_at'] as $column) {
                if (Schema::hasColumn('vehicle_patrols', $column)) {
                    if (in_array($column, ['vehicle_id', 'guard_id'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::dropIfExists('fleet_vehicles');
    }
};
