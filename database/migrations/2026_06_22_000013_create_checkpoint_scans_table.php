<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoint_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrol_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrol_checkpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude',10,7)->nullable();
            $table->decimal('longitude',10,7)->nullable();
            $table->timestamp('scanned_at');
            $table->string('status')->default('valid');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_scans');
    }
};
