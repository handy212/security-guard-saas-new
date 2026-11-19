<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jwm_guard_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jwm_device_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 100);
            $table->char('external_id_hash', 64);
            $table->foreignId('guard_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['jwm_device_id', 'external_id_hash'], 'jwm_guard_external_id');
        });

        Schema::create('jwm_checkpoint_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jwm_device_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 100);
            $table->char('external_id_hash', 64);
            $table->foreignId('patrol_checkpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['jwm_device_id', 'external_id_hash'], 'jwm_checkpoint_external_id');
        });

        Schema::table('jwm_devices', function (Blueprint $table) {
            $table->boolean('require_device_identity')->default(false);
        });
        Schema::table('jwm_device_events', function (Blueprint $table) {
            $table->string('guard_external_id', 100)->nullable();
            $table->string('checkpoint_external_id', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jwm_device_events', function (Blueprint $table) {
            $table->dropColumn(['guard_external_id', 'checkpoint_external_id']);
        });
        Schema::table('jwm_devices', function (Blueprint $table) {
            $table->dropColumn('require_device_identity');
        });
        Schema::dropIfExists('jwm_checkpoint_mappings');
        Schema::dropIfExists('jwm_guard_mappings');
    }
};
