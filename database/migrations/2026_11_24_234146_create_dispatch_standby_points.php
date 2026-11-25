<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_standby_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });

        Schema::table('dispatch_teams', function (Blueprint $table) {
            $table->foreignId('standby_point_id')->nullable()->constrained('dispatch_standby_points')->nullOnDelete();
        });

        DB::table('sites')->whereNotNull('dispatch_region_id')
            ->whereBetween('latitude', [-90, 90])
            ->whereBetween('longitude', [-180, 180])
            ->where(fn ($query) => $query->where('latitude', '!=', 0)->orWhere('longitude', '!=', 0))
            ->chunkById(200, function ($sites): void {
                foreach ($sites as $site) {
                    if (! DB::table('branches')->where('id', $site->dispatch_region_id)->where('tenant_id', $site->tenant_id)->exists()) {
                        continue;
                    }

                    $point = DB::table('dispatch_standby_points')->insertGetId([
                        'tenant_id' => $site->tenant_id,
                        'region_id' => $site->dispatch_region_id,
                        'name' => $site->name,
                        'latitude' => $site->latitude,
                        'longitude' => $site->longitude,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('dispatch_teams')->where('tenant_id', $site->tenant_id)
                        ->where('standby_site_id', $site->id)->update(['standby_point_id' => $point]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('dispatch_teams', fn (Blueprint $table) => $table->dropConstrainedForeignId('standby_point_id'));
        Schema::dropIfExists('dispatch_standby_points');
    }
};
