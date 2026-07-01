<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('email');
            $table->string('rank')->nullable()->after('photo_path');
            $table->foreignId('branch_id')->nullable()->after('rank')->constrained()->nullOnDelete();
            $table->string('verification_status')->default('unverified')->after('status');
            $table->timestamp('verified_at')->nullable()->after('verification_status');
            $table->foreignId('verified_by_user_id')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->boolean('show_current_assignment')->default(false)->after('verified_by_user_id');
        });

        Schema::create('guard_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->string('token', 32)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();

            $table->index(['guard_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_verification_tokens');

        Schema::table('guards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('verified_by_user_id');
            $table->dropColumn([
                'photo_path', 'rank', 'verification_status', 'verified_at', 'show_current_assignment',
            ]);
        });
    }
};
