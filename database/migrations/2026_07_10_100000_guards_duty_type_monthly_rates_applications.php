<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            if (! Schema::hasColumn('guards', 'duty_type')) {
                $table->string('duty_type', 32)->default('guardian');
            }
        });

        if (Schema::hasColumn('guards', 'hourly_rate') && ! Schema::hasColumn('guards', 'monthly_rate')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->decimal('monthly_rate', 10, 2)->default(0);
            });
            DB::table('guards')->update([
                'monthly_rate' => DB::raw('hourly_rate'),
            ]);
            Schema::table('guards', function (Blueprint $table) {
                $table->dropColumn('hourly_rate');
            });
        } elseif (! Schema::hasColumn('guards', 'monthly_rate')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->decimal('monthly_rate', 10, 2)->default(0);
            });
        }

        if (Schema::hasColumn('client_accounts', 'default_hourly_rate') && ! Schema::hasColumn('client_accounts', 'default_monthly_rate')) {
            Schema::table('client_accounts', function (Blueprint $table) {
                $table->decimal('default_monthly_rate', 10, 2)->default(0);
            });
            DB::table('client_accounts')->update([
                'default_monthly_rate' => DB::raw('default_hourly_rate'),
            ]);
            Schema::table('client_accounts', function (Blueprint $table) {
                $table->dropColumn('default_hourly_rate');
            });
        } elseif (! Schema::hasColumn('client_accounts', 'default_monthly_rate')) {
            Schema::table('client_accounts', function (Blueprint $table) {
                $table->decimal('default_monthly_rate', 10, 2)->default(0);
            });
        }

        if (! Schema::hasTable('guard_applications')) {
            Schema::create('guard_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('duty_type', 32)->default('guardian');
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->text('notes')->nullable();
                $table->string('photo_path')->nullable();
                $table->string('status', 32)->default('pending');
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('guard_id')->nullable()->constrained('guards')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_applications');

        if (Schema::hasColumn('guards', 'duty_type')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->dropColumn('duty_type');
            });
        }

        if (Schema::hasColumn('guards', 'monthly_rate') && ! Schema::hasColumn('guards', 'hourly_rate')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->decimal('hourly_rate', 10, 2)->default(0);
            });
            DB::table('guards')->update([
                'hourly_rate' => DB::raw('monthly_rate'),
            ]);
            Schema::table('guards', function (Blueprint $table) {
                $table->dropColumn('monthly_rate');
            });
        }

        if (Schema::hasColumn('client_accounts', 'default_monthly_rate') && ! Schema::hasColumn('client_accounts', 'default_hourly_rate')) {
            Schema::table('client_accounts', function (Blueprint $table) {
                $table->decimal('default_hourly_rate', 10, 2)->default(0);
            });
            DB::table('client_accounts')->update([
                'default_hourly_rate' => DB::raw('default_monthly_rate'),
            ]);
            Schema::table('client_accounts', function (Blueprint $table) {
                $table->dropColumn('default_monthly_rate');
            });
        }
    }
};
