<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('standby_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('name');
            $table->unique(['tenant_id', 'name']);
            $table->timestamps();
        });
        Schema::table('guards', function (Blueprint $table) {
            $table->foreignId('dispatch_team_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->foreignId('dispatch_team_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->index(['dispatch_team_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_events', function (Blueprint $table) {
            $table->dropIndex(['dispatch_team_id', 'returned_at']);
            $table->dropConstrainedForeignId('dispatch_team_id');
            $table->dropColumn(['returned_at', 'acknowledged_at']);
        });
        Schema::table('guards', fn (Blueprint $table) => $table->dropConstrainedForeignId('dispatch_team_id'));
        Schema::dropIfExists('dispatch_teams');
    }
};
