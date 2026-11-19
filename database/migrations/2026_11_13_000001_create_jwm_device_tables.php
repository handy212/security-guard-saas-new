<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jwm_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('reader_number', 100);
            $table->string('model')->default('JWM5000ZG');
            $table->boolean('is_active')->default(true);
            $table->foreignId('bridge_token_id')->nullable()->constrained('personal_access_tokens')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'reader_number']);
        });

        Schema::create('jwm_device_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jwm_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->index(['jwm_device_id', 'starts_at', 'ends_at'], 'jwm_assignment_interval');
        });

        Schema::create('jwm_device_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jwm_device_id')->constrained()->cascadeOnDelete();
            $table->string('event_id', 100);
            $table->char('payload_hash', 64);
            $table->string('type', 20);
            $table->dateTime('occurred_at');
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkpoint_scan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['jwm_device_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jwm_device_events');
        Schema::dropIfExists('jwm_device_assignments');
        Schema::dropIfExists('jwm_devices');
    }
};
