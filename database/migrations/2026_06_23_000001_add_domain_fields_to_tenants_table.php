<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'domain')) {
                $table->string('domain')->nullable()->unique()->after('slug');
            }
            if (! Schema::hasColumn('tenants', 'subdomain')) {
                $table->string('subdomain')->nullable()->unique()->after('domain');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['domain', 'subdomain']);
        });
    }
};
