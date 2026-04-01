<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'technical_specs')) {
                $table->json('technical_specs')->nullable()->after('description');
            }
        });

        if (!Schema::hasTable('product_specifications')) {
            return;
        }

        $productIds = DB::table('products')->pluck('id');

        foreach ($productIds as $productId) {
            $specs = DB::table('product_specifications')
                ->where('product_id', $productId)
                ->orderBy('id')
                ->get(['spec_key', 'spec_value']);

            if ($specs->isEmpty()) {
                continue;
            }

            $technicalSpecs = [];

            foreach ($specs as $spec) {
                if ($spec->spec_key === null || $spec->spec_value === null) {
                    continue;
                }

                $technicalSpecs[$spec->spec_key] = $spec->spec_value;
            }

            if ($technicalSpecs === []) {
                continue;
            }

            DB::table('products')
                ->where('id', $productId)
                ->update([
                    'technical_specs' => json_encode($technicalSpecs, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'technical_specs')) {
                $table->dropColumn('technical_specs');
            }
        });
    }
};
