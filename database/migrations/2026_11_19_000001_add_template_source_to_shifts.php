<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('source_template_item_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('shift_template_items')
                ->nullOnDelete();
            $table->date('template_week_start')->nullable()->after('source_template_item_id');
            $table->unique(
                ['source_template_item_id', 'template_week_start'],
                'shifts_template_item_week_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique('shifts_template_item_week_unique');
            $table->dropConstrainedForeignId('source_template_item_id');
            $table->dropColumn('template_week_start');
        });
    }
};
