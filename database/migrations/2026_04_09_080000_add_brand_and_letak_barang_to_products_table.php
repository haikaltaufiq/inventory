<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand')->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('products', 'letak_barang')) {
                $table->string('letak_barang')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'letak_barang')) {
                $table->dropColumn('letak_barang');
            }

            if (Schema::hasColumn('products', 'brand')) {
                $table->dropColumn('brand');
            }
        });
    }
};
