<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'transaction_mode')) {
                $table->string('transaction_mode', 30)->default('sparepart')->after('sales_name');
            }

            if (!Schema::hasColumn('transactions', 'installation_fee')) {
                $table->decimal('installation_fee', 15, 2)->default(0)->after('service_fee');
            }

            if (!Schema::hasColumn('transactions', 'service_labor_fee')) {
                $table->decimal('service_labor_fee', 15, 2)->default(0)->after('installation_fee');
            }

            if (!Schema::hasColumn('transactions', 'shipping_fee')) {
                $table->decimal('shipping_fee', 15, 2)->default(0)->after('service_labor_fee');
            }

            if (!Schema::hasColumn('transactions', 'marketing_fee')) {
                $table->decimal('marketing_fee', 15, 2)->default(0)->after('shipping_fee');
            }
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_details', 'item_name')) {
                $table->string('item_name')->nullable()->after('product_supplier_id');
            }

            if (!Schema::hasColumn('transaction_details', 'item_specification')) {
                $table->text('item_specification')->nullable()->after('item_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['transaction_mode', 'installation_fee', 'service_labor_fee', 'shipping_fee', 'marketing_fee'] as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['item_name', 'item_specification'] as $column) {
                if (Schema::hasColumn('transaction_details', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
