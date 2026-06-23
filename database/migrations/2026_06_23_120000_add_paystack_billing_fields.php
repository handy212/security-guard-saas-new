<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'paystack_customer_code')) {
                $table->string('paystack_customer_code')->nullable()->after('plan_id');
            }
            if (! Schema::hasColumn('tenants', 'paystack_subscription_code')) {
                $table->string('paystack_subscription_code')->nullable()->after('paystack_customer_code');
            }
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'paystack_plan_code')) {
                $table->string('paystack_plan_code')->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            foreach (['paystack_customer_code', 'paystack_subscription_code'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'paystack_plan_code')) {
                $table->dropColumn('paystack_plan_code');
            }
        });
    }
};
