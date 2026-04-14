<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class TransactionRepository
{
    /**
     * @return Collection
     */
    public function getSalesUsers(): Collection
    {
        return User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection
     */
    public function getCategories(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection
     */
    public function getProductsForIndex(): Collection
    {
        return Product::query()
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.image_url',
                'products.description',
            ])
            ->with([
                'category:id,name',
                'specifications:id,product_id,spec_key,spec_value',
                'suppliers' => function ($query) {
                    $query
                        ->select('suppliers.id', 'suppliers.nama_supplier')
                        ->where('product_supplier.stock', '>', 0)
                        ->withPivot('id', 'stock', 'harga_jual_manual', 'condition');
                },
            ])
            ->get()
            ->map(function (Product $product) {
                $specs = collect($product->specifications)->pluck('spec_value', 'spec_key');
                $specItems = collect($product->specifications)
                    ->map(function ($spec) {
                        return [
                            'key' => $spec->spec_key,
                            'value' => $spec->spec_value,
                        ];
                    })
                    ->values()
                    ->all();

                $suppliers = collect($product->suppliers)
                    ->map(function ($supplier) {
                        return [
                            'id' => $supplier->id,
                            'supplier_id' => $supplier->id,
                            'nama_supplier' => $supplier->nama_supplier,
                            'pivot' => [
                                'id' => $supplier->pivot->id,
                                'stock' => (int) $supplier->pivot->stock,
                                'harga_jual_manual' => (float) $supplier->pivot->harga_jual_manual,
                                'condition' => $supplier->pivot->condition,
                            ],
                        ];
                    })
                    ->values();

                $basePrice = $suppliers
                    ->pluck('pivot.harga_jual_manual')
                    ->filter(fn($price) => $price !== null)
                    ->min();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => ['name' => $product->category?->name ?? 'Uncategorized'],
                    'base_price' => (float) ($basePrice ?? 0),
                    'socket' => $specs->get('socket'),
                    'ram_type' => $specs->get('ram_type'),
                    'image_url' => $product->image_url ?? asset('assets/no-image.svg'),
                    'description' => $product->description,
                    'specs' => $specItems,
                    'suppliers' => $suppliers,
                ];
            })
            ->values();
    }

    /**
     * @return Collection
     */
    public function getCustomersForCreate(): Collection
    {
        return Customer::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection
     */
    public function getProductsForCreate(): Collection
    {
        return Product::query()
            ->select('id', 'name')
            ->withSum('suppliers as total_stock', 'product_supplier.stock')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param Product $product
     * @return Collection
     */
    public function getSuppliersByProduct(Product $product): Collection
    {
        $product->load([
            'suppliers' => function ($query) {
                $query
                    ->select('suppliers.id', 'suppliers.nama_supplier')
                    ->where('product_supplier.stock', '>', 0)
                    ->withPivot('id', 'condition', 'stock', 'harga_jual_manual');
            },
        ]);

        return collect($product->suppliers)
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'supplier_id' => $supplier->id,
                    'product_supplier_id' => $supplier->pivot->id,
                    'nama_supplier' => $supplier->nama_supplier,
                    'condition' => $supplier->pivot->condition,
                    'stock' => (int) $supplier->pivot->stock,
                    'harga_jual' => (float) $supplier->pivot->harga_jual_manual,
                ];
            })
            ->values();
    }
}
