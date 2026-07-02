<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_categories')) {
            Schema::create('asset_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type')->default('serialized');
                $table->unsignedInteger('min_stock_level')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('asset_vendors')) {
            Schema::create('asset_vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('contact_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('asset_purchase_orders')) {
            Schema::create('asset_purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('asset_vendors')->cascadeOnDelete();
                $table->string('po_number');
                $table->string('status')->default('draft');
                $table->date('order_date')->nullable();
                $table->date('expected_date')->nullable();
                $table->date('received_date')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['tenant_id', 'po_number']);
            });
        }

        if (! Schema::hasTable('asset_purchase_order_items')) {
            Schema::create('asset_purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->constrained('asset_purchase_orders')->cascadeOnDelete();
                $table->foreignId('asset_category_id')->nullable()->constrained()->nullOnDelete();
                $table->string('description');
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedInteger('quantity_received')->default(0);
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('equipment_assets')) {
            Schema::table('equipment_assets', function (Blueprint $table) {
                if (! Schema::hasColumn('equipment_assets', 'asset_category_id')) {
                    $table->foreignId('asset_category_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('equipment_assets', 'vendor_id')) {
                    $table->foreignId('vendor_id')->nullable()->after('asset_category_id')->constrained('asset_vendors')->nullOnDelete();
                }
                if (! Schema::hasColumn('equipment_assets', 'purchase_order_id')) {
                    $table->foreignId('purchase_order_id')->nullable()->after('vendor_id')->constrained('asset_purchase_orders')->nullOnDelete();
                }
                if (! Schema::hasColumn('equipment_assets', 'site_id')) {
                    $table->foreignId('site_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('equipment_assets', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (! Schema::hasColumn('equipment_assets', 'model')) {
                    $table->string('model')->nullable()->after('serial_number');
                }
                if (! Schema::hasColumn('equipment_assets', 'manufacturer')) {
                    $table->string('manufacturer')->nullable()->after('model');
                }
                if (! Schema::hasColumn('equipment_assets', 'purchase_cost')) {
                    $table->decimal('purchase_cost', 12, 2)->nullable()->after('manufacturer');
                }
                if (! Schema::hasColumn('equipment_assets', 'purchase_date')) {
                    $table->date('purchase_date')->nullable()->after('purchase_cost');
                }
                if (! Schema::hasColumn('equipment_assets', 'warranty_expires_at')) {
                    $table->date('warranty_expires_at')->nullable()->after('purchase_date');
                }
                if (! Schema::hasColumn('equipment_assets', 'location')) {
                    $table->string('location')->nullable()->after('warranty_expires_at');
                }
                if (! Schema::hasColumn('equipment_assets', 'quantity_on_hand')) {
                    $table->unsignedInteger('quantity_on_hand')->default(1)->after('location');
                }
                if (! Schema::hasColumn('equipment_assets', 'notes')) {
                    $table->text('notes')->nullable()->after('quantity_on_hand');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('equipment_assets')) {
            Schema::table('equipment_assets', function (Blueprint $table) {
                foreach ([
                    'asset_category_id', 'vendor_id', 'purchase_order_id', 'site_id',
                    'description', 'model', 'manufacturer', 'purchase_cost', 'purchase_date',
                    'warranty_expires_at', 'location', 'quantity_on_hand', 'notes',
                ] as $column) {
                    if (Schema::hasColumn('equipment_assets', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('asset_purchase_order_items');
        Schema::dropIfExists('asset_purchase_orders');
        Schema::dropIfExists('asset_vendors');
        Schema::dropIfExists('asset_categories');
    }
};
