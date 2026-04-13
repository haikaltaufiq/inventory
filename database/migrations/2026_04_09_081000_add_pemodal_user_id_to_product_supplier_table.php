<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // Pastikan FK product_id/supplier_id punya index sendiri agar tidak "numpang"
        // ke unique lama ketika unique tersebut diganti.
        if (!$this->hasIndex('product_supplier', 'product_supplier_product_id_idx')) {
            Schema::table('product_supplier', function (Blueprint $table) {
                $table->index('product_id', 'product_supplier_product_id_idx');
            });
        }

        if (!$this->hasIndex('product_supplier', 'product_supplier_supplier_id_idx')) {
            Schema::table('product_supplier', function (Blueprint $table) {
                $table->index('supplier_id', 'product_supplier_supplier_id_idx');
            });
        }

        Schema::table('product_supplier', function (Blueprint $table) {
            if ($this->hasIndex('product_supplier', 'prod_supp_cond_unique')) {
                $table->dropUnique('prod_supp_cond_unique');
            }

            if (!$this->hasIndex('product_supplier', 'prod_supp_cond_pemodal_unique')) {
                $table->unique(
                    ['product_id', 'supplier_id', 'condition', 'pemodal_user_id'],
                    'prod_supp_cond_pemodal_unique'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_supplier', function (Blueprint $table) {
            if ($this->hasIndex('product_supplier', 'prod_supp_cond_pemodal_unique')) {
                $table->dropUnique('prod_supp_cond_pemodal_unique');
            }

            if (!$this->hasIndex('product_supplier', 'prod_supp_cond_unique')) {
                $table->unique(['product_id', 'supplier_id', 'condition'], 'prod_supp_cond_unique');
            }
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            if (Schema::hasColumn('product_supplier', 'pemodal_user_id')) {
                $table->dropConstrainedForeignId('pemodal_user_id');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
