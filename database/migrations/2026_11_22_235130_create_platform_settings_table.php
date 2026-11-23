<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->json('value');
        });

        DB::table('platform_settings')->insert(['key' => 'branding', 'value' => '{}']);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
