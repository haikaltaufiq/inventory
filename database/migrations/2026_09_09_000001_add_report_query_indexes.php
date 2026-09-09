<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->index('transaction_id', 'transaction_details_transaction_id_idx');
        });

        Schema::table('product_spec_value', function (Blueprint $table) {
            $table->index('spec_value_preset_id', 'product_spec_value_preset_id_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('created_at', 'products_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropIndex('transaction_details_transaction_id_idx');
        });

        Schema::table('product_spec_value', function (Blueprint $table) {
            $table->dropIndex('product_spec_value_preset_id_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_created_at_idx');
        });
    }
};
