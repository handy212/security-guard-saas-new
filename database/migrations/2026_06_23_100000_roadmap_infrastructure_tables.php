<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guard_locations')) {
            Schema::create('guard_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('accuracy_meters', 8, 2)->nullable();
                $table->string('source')->default('mobile');
                $table->timestamp('recorded_at');
                $table->timestamps();
                $table->index(['tenant_id', 'guard_id', 'recorded_at']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'client_account_id')) {
                $table->foreignId('client_account_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('plan_id');
            }
            if (! Schema::hasColumn('tenants', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            }
        });

        if (! Schema::hasTable('webhook_subscriptions')) {
            Schema::create('webhook_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('event');
                $table->string('target_url');
                $table->string('secret');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_delivered_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('guard_locations');

        Schema::table('users', function (Blueprint $table) {
            foreach (['client_account_id', 'two_factor_secret', 'two_factor_confirmed_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            foreach (['stripe_customer_id', 'stripe_subscription_id'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
