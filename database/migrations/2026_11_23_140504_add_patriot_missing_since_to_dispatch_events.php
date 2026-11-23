<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_events', fn (Blueprint $table) => $table->timestamp('patriot_missing_since')->nullable());
    }

    public function down(): void
    {
        Schema::table('dispatch_events', fn (Blueprint $table) => $table->dropColumn('patriot_missing_since'));
    }
};
