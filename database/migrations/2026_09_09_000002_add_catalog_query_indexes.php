<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_supplier', function (Blueprint $table) {
            $table->index(['product_id', 'stock'], 'product_supplier_product_stock_idx');
        });

        Schema::table('spec_value_presets', function (Blueprint $table) {
            $table->index(['spec_key', 'spec_value'], 'spec_value_presets_key_value_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_supplier', function (Blueprint $table) {
            $table->dropIndex('product_supplier_product_stock_idx');
        });

        Schema::table('spec_value_presets', function (Blueprint $table) {
            $table->dropIndex('spec_value_presets_key_value_idx');
        });
    }
};
