<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductRepository
{
    public function getForIndex(Request $request): Builder
    {
        $query = Product::query()
            ->select([
                'products.id',
                'products.category_id',
                'products.brand',
                'products.name',
                'products.serial_number',
                'products.letak_barang',
                'products.description',
                'products.image_url',
            ])
            ->with([
                'category:id,name',
                'specs' => fn ($q) => $q->select('spec_value_presets.id', 'spec_key', 'spec_value'),
                'suppliers' => function ($query) {
                    $pivotFields = [
                        'condition',
                        'stock',
                        'harga_beli',
                        'harga_jual_manual',
                        'warranty_detail',
                    ];

                    if ($this->supportsProductSupplierPemodalColumn()) {
                        $pivotFields[] = 'pemodal_user_id';
                    }

                    $query
                        ->select('suppliers.id', 'suppliers.nama_supplier')
                        ->withPivot(...$pivotFields);
                },
            ]);

        return $this->applyProductIndexFilters($query, $request)
            ->orderByDesc('products.id');
    }

    public function getIndexSummary(Request $request): array
    {
        $summaryRow = $this->applyProductIndexFilters(
            Product::query()
                ->leftJoin('product_supplier', 'product_supplier.product_id', '=', 'products.id')
                ->selectRaw('COUNT(DISTINCT products.id) as total_produk')
                ->selectRaw('COALESCE(SUM(product_supplier.stock), 0) as total_stok')
                ->selectRaw('COALESCE(SUM(product_supplier.stock * product_supplier.harga_beli), 0) as nilai_inv')
                ->selectRaw('COALESCE(SUM(CASE WHEN product_supplier.stock <= 10 THEN 1 ELSE 0 END), 0) as stok_menipis'),
            $request
        )->first();

        return [
            'total_produk' => (int) ($summaryRow->total_produk ?? 0),
            'total_stok' => (int) ($summaryRow->total_stok ?? 0),
            'nilai_inv' => (float) ($summaryRow->nilai_inv ?? 0),
            'stok_menipis' => (int) ($summaryRow->stok_menipis ?? 0),
        ];
    }

    private function applyProductIndexFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.serial_number', 'like', "%{$search}%");

                if (strlen($search) === 4 && ctype_digit($search)) {
                    $q->orWhere('products.serial_number', 'like', "%{$search}");
                }
            });
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        return $query;
    }

    private function supportsProductSupplierPemodalColumn(): bool
    {
        static $supportsProductSupplierPemodal;

        if ($supportsProductSupplierPemodal === null) {
            $supportsProductSupplierPemodal = Schema::hasColumn('product_supplier', 'pemodal_user_id');
        }

        return $supportsProductSupplierPemodal;
    }
}
