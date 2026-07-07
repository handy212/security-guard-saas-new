<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('hire_date');
        });

        Schema::create('guard_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });

        Schema::create('guard_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->timestamp('due_at');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guard_site_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['guard_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_site_assignments');
        Schema::dropIfExists('guard_reminders');
        Schema::dropIfExists('guard_notes');

        Schema::table('guards', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
