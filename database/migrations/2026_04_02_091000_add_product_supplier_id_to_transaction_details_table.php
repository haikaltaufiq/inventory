<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transaction_details', 'product_supplier_id')) {
            return;
        }

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_supplier_id')->nullable()->after('supplier_id');
            $table->index('product_supplier_id');
        });

        DB::table('transaction_details')
            ->orderBy('id')
            ->get(['id', 'product_id', 'supplier_id'])
            ->each(function ($detail) {
                $productSupplierId = DB::table('product_supplier')
                    ->where('product_id', $detail->product_id)
                    ->where('supplier_id', $detail->supplier_id)
                    ->orderBy('id')
                    ->value('id');

                if ($productSupplierId === null) {
                    return;
                }

                DB::table('transaction_details')
                    ->where('id', $detail->id)
                    ->update(['product_supplier_id' => $productSupplierId]);
            });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('transaction_details', 'product_supplier_id')) {
            return;
        }

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropIndex(['product_supplier_id']);
            $table->dropColumn('product_supplier_id');
        });
    }
};
