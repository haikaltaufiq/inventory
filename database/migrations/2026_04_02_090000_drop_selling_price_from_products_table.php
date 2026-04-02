<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'selling_price')) {
            return;
        }

        $fallbackPrices = DB::table('products')
            ->select('id', 'selling_price')
            ->whereNotNull('selling_price')
            ->pluck('selling_price', 'id');

        DB::table('product_supplier')
            ->whereNull('harga_jual_manual')
            ->orderBy('id')
            ->get(['id', 'product_id'])
            ->each(function ($row) use ($fallbackPrices) {
                $fallbackPrice = $fallbackPrices->get($row->product_id);

                if ($fallbackPrice === null) {
                    return;
                }

                DB::table('product_supplier')
                    ->where('id', $row->id)
                    ->update([
                        'harga_jual_manual' => $fallbackPrice,
                        'updated_at' => now(),
                    ]);
            });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('selling_price');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'selling_price')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('selling_price', 15, 2)->default(0)->after('name');
        });
    }
};
