<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['transaction_date', 'id'], 'transactions_transaction_date_id_idx');
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            $table->index('stock', 'product_supplier_stock_idx');
        });

        // DIGANTI: product_specifications sudah tidak ada
        // Index sekarang di product_spec_value (pivot baru)
        // Schema::table('product_spec_value', function (Blueprint $table) {
        //     $table->index('product_id', 'product_spec_value_product_id_idx');
        //     $table->index('spec_value_preset_id', 'product_spec_value_preset_id_idx');
        // });

        // Index di spec_value_presets untuk lookup dropdown
        Schema::table('spec_value_presets', function (Blueprint $table) {
            $table->index('spec_key', 'spec_value_presets_spec_key_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('phone', 'customers_phone_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('name', 'categories_name_idx');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('nama_supplier', 'suppliers_nama_supplier_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_transaction_date_id_idx');
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            $table->dropIndex('product_supplier_stock_idx');
        });

        // Schema::table('product_spec_value', function (Blueprint $table) {
        //     $table->dropIndex('product_spec_value_product_id_idx');
        //     $table->dropIndex('product_spec_value_preset_id_idx');
        // });

        Schema::table('spec_value_presets', function (Blueprint $table) {
            $table->dropIndex('spec_value_presets_spec_key_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_phone_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_name_idx');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('suppliers_nama_supplier_idx');
        });
    }
};
