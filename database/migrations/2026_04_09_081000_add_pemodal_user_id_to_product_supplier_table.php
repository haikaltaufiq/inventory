<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_supplier', function (Blueprint $table) {
            if (!Schema::hasColumn('product_supplier', 'pemodal_user_id')) {
                $table->foreignId('pemodal_user_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            $table->dropUnique('prod_supp_cond_unique');
            $table->unique(
                ['product_id', 'supplier_id', 'condition', 'pemodal_user_id'],
                'prod_supp_cond_pemodal_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_supplier', function (Blueprint $table) {
            $table->dropUnique('prod_supp_cond_pemodal_unique');
            $table->unique(['product_id', 'supplier_id', 'condition'], 'prod_supp_cond_unique');
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            if (Schema::hasColumn('product_supplier', 'pemodal_user_id')) {
                $table->dropConstrainedForeignId('pemodal_user_id');
            }
        });
    }
};
