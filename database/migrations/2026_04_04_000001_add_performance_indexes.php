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

        Schema::table('product_specifications', function (Blueprint $table) {
            $table->index(['product_id', 'spec_key'], 'product_specifications_product_spec_key_idx');
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

        Schema::table('product_specifications', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex('product_specifications_product_spec_key_idx');
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
