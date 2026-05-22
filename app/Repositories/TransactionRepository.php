<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Support\CacheVersions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TransactionRepository
{
    public function getSalesUsers(): Collection
    {
        return Cache::remember('transactions:sales_users', now()->addMinutes(15), fn () => User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get());
    }

    public function getCategories(): Collection
    {
        return Cache::remember('transactions:categories', now()->addMinutes(15), fn () => Category::query()
            ->orderBy('name')
            ->get(['id', 'name']));
    }

    public function getProductsForIndex(?Request $request = null): Collection|LengthAwarePaginator
    {
        if ($request) {
            return $this->getProductsForIndexPage($request);
        }

        return $this->productIndexQuery()
            ->get()
            ->map(fn (Product $product) => $this->formatProductForIndex($product))
            ->values();
    }

    public function getProductsForIndexPage(Request $request): LengthAwarePaginator
    {
        $perPage = min(120, max(24, (int) $request->integer('per_page', 72)));
        $query = $this->productIndexQuery();

        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            $query->whereIn('products.id', $ids);
            $perPage = max($perPage, $ids->count());
        } else {
            if ($request->filled('category') && $request->input('category') !== 'Semua') {
                $category = trim((string) $request->input('category'));
                $query->whereHas('category', fn ($q) => $q->where('name', $category));
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->input('search'));
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($search, $like) {
                    $q->where('products.name', 'like', $like)
                        ->orWhere('products.serial_number', 'like', $like);

                    if (strlen($search) === 4 && ctype_digit($search)) {
                        $q->orWhere('products.serial_number', 'like', '%' . $search);
                    }
                });
            }
        }

        $page = (int) $request->integer('page', 1);
        $cacheKey = 'transactions:products:v'.CacheVersions::catalog().':'.
            md5(json_encode([
                'page' => $page,
                'per_page' => $perPage,
                'ids' => $ids->all(),
                'category' => $request->input('category'),
                'search' => trim((string) $request->input('search', '')),
            ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), fn () => $query
            ->orderBy('products.name')
            ->paginate($perPage)
            ->through(fn (Product $product) => $this->formatProductForIndex($product)));
    }

    private function productIndexQuery()
    {
        return Product::query()
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.serial_number',
                'products.image_url',
                'products.description',
            ])
            ->with([
                'category:id,name',
                'specs' => fn ($q) => $q->select('spec_value_presets.id', 'spec_key', 'spec_value'),
                'suppliers' => function ($query) {
                    $query
                        ->select('suppliers.id', 'suppliers.nama_supplier')
                        ->where('product_supplier.stock', '>', 0)
                        ->withPivot('id', 'stock', 'harga_jual_manual', 'condition');
                },
            ]);
    }

    private function formatProductForIndex(Product $product): array
    {
        $specs = collect($product->specs)->pluck('spec_value', 'spec_key');
        $specItems = collect($product->specs)
            ->map(fn ($spec) => [
                'key' => $spec->spec_key,
                'value' => $spec->spec_value,
            ])
            ->values()
            ->all();

        $suppliers = collect($product->suppliers)
            ->map(fn ($supplier) => [
                'id' => $supplier->id,
                'supplier_id' => $supplier->id,
                'nama_supplier' => $supplier->nama_supplier,
                'pivot' => [
                    'id' => $supplier->pivot->id,
                    'stock' => (int) $supplier->pivot->stock,
                    'harga_jual_manual' => (float) $supplier->pivot->harga_jual_manual,
                    'condition' => $supplier->pivot->condition,
                ],
            ])
            ->values();

        $basePrice = $suppliers
            ->pluck('pivot.harga_jual_manual')
            ->filter(fn ($price) => $price !== null)
            ->min();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'serial_number' => $product->serial_number,
            'category' => ['name' => $product->category?->name ?? 'Uncategorized'],
            'base_price' => (float) ($basePrice ?? 0),
            'socket' => $specs->get('socket_type') ?? $specs->get('socket'),
            'ram_type' => $specs->get('ram_type'),
            'image_url' => $product->image_url ?? asset('assets/no-image.svg'),
            'description' => $product->description,
            'specs' => $specItems,
            'suppliers' => $suppliers,
        ];
    }

    public function getCustomersForCreate(): Collection
    {
        return Customer::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }

    public function getProductsForCreate(): Collection
    {
        return Product::query()
            ->select('id', 'name')
            ->withSum('suppliers as total_stock', 'product_supplier.stock')
            ->orderBy('name')
            ->get();
    }

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
