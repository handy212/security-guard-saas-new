<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->reconcileInvoices();
        $this->reconcileInvoiceItems();
    }

    private function reconcileInvoices(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        if (Schema::hasColumn('invoices', 'invoice_no') && Schema::hasColumn('invoices', 'invoice_number')) {
            DB::table('invoices')
                ->whereNull('invoice_number')
                ->update(['invoice_number' => DB::raw('invoice_no')]);
        }

        if (Schema::hasColumn('invoices', 'issue_date') && Schema::hasColumn('invoices', 'invoice_date')) {
            DB::table('invoices')
                ->whereNull('invoice_date')
                ->update(['invoice_date' => DB::raw('issue_date')]);
        }

        if (Schema::hasColumn('invoices', 'tax') && Schema::hasColumn('invoices', 'tax_total')) {
            DB::table('invoices')
                ->where('tax_total', 0)
                ->where('tax', '>', 0)
                ->update(['tax_total' => DB::raw('tax')]);
        }

        if (Schema::hasColumn('invoices', 'total') && Schema::hasColumn('invoices', 'grand_total')) {
            DB::table('invoices')
                ->where('grand_total', 0)
                ->where('total', '>', 0)
                ->update(['grand_total' => DB::raw('total')]);
        }

        Schema::table('invoices', function (Blueprint $table) {
            $legacy = array_filter([
                Schema::hasColumn('invoices', 'invoice_no') && Schema::hasColumn('invoices', 'invoice_number') ? 'invoice_no' : null,
                Schema::hasColumn('invoices', 'issue_date') && Schema::hasColumn('invoices', 'invoice_date') ? 'issue_date' : null,
                Schema::hasColumn('invoices', 'tax') && Schema::hasColumn('invoices', 'tax_total') ? 'tax' : null,
                Schema::hasColumn('invoices', 'total') && Schema::hasColumn('invoices', 'grand_total') ? 'total' : null,
            ]);

            if ($legacy !== []) {
                $table->dropColumn($legacy);
            }
        });
    }

    private function reconcileInvoiceItems(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        if (Schema::hasColumn('invoice_items', 'total') && Schema::hasColumn('invoice_items', 'line_total')) {
            DB::table('invoice_items')
                ->where('line_total', 0)
                ->where('total', '>', 0)
                ->update(['line_total' => DB::raw('total')]);
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'total') && Schema::hasColumn('invoice_items', 'line_total')) {
                $table->dropColumn('total');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'invoice_no')) {
                $table->string('invoice_no')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('invoices', 'total')) {
                $table->decimal('total', 12, 2)->default(0);
            }
        });

        if (Schema::hasColumn('invoices', 'invoice_no') && Schema::hasColumn('invoices', 'invoice_number')) {
            DB::table('invoices')->update(['invoice_no' => DB::raw('invoice_number')]);
        }

        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'total')) {
                $table->decimal('total', 12, 2)->default(0);
            }
        });

        if (Schema::hasColumn('invoice_items', 'total') && Schema::hasColumn('invoice_items', 'line_total')) {
            DB::table('invoice_items')->update(['total' => DB::raw('line_total')]);
        }
    }
};
