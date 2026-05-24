<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\CacheVersions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class ProductService
{
    private const SPEC_OPTIONS_CACHE_KEY = 'products.spec_options';

    private function getCloudinary(): Cloudinary
    {
        $config = Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);

        return new Cloudinary($config);
    }
    
    public function __construct(
        private ProductSpecService $specService
    ) {}

    public function createProduct(array $validated, ?UploadedFile $imageFile = null): Product
    {
        return DB::transaction(function () use ($validated, $imageFile) {
            return $this->persistProduct(null, $validated, $imageFile);
        });
    }

    public function updateProduct(Product $product, array $validated, ?UploadedFile $imageFile = null): Product
    {
        return DB::transaction(function () use ($product, $validated, $imageFile) {
            return $this->persistProduct($product, $validated, $imageFile);
        });
    }

    public function deleteProduct(Product $product): void
    {
        $this->deleteProductImage($product);
        $product->delete();
        $this->forgetProductOptionCaches();
        CacheVersions::bumpCatalog();
    }

    public function persistProduct(?Product $product, array $validated, ?UploadedFile $imageFile = null): Product
    {
        $category = Category::query()->findOrFail($validated['category_id']);
        $specPayload = $this->specService->buildSpecificationPayload($validated, $category->name);
        $resolvedSuppliers = $this->resolveSupplierPayloads($validated['suppliers']);

        $attributes = [
            'category_id' => $validated['category_id'],
            'brand' => $this->specService->nullableTrim($validated['brand'] ?? null),
            'name' => trim($validated['name']),
            'serial_number' => $this->specService->nullableTrim($validated['serial_number'] ?? null),
            'letak_barang' => $this->specService->nullableTrim($validated['letak_barang'] ?? null),
            'description' => $this->specService->nullableTrim($validated['description'] ?? null),
        ];

        if ($product === null) {
            $attributes['image_url'] = $this->storeUploadedProductImage($imageFile);
            $product = Product::create($attributes);
        } else {
            $attributes['image_url'] = $this->replaceUploadedProductImage($product, $imageFile);
            $product->update($attributes);
        }

        $this->specService->syncSpecs($product, $specPayload);
        $this->syncProductSuppliers($product, $resolvedSuppliers);
        $this->forgetProductOptionCaches();
        CacheVersions::bumpCatalog();

        return $product;
    }

    private function storeUploadedProductImage(?UploadedFile $imageFile): ?string
    {
        if ($imageFile === null) {
            return null;
        }

        $result = $this->getCloudinary()
            ->uploadApi()
            ->upload($imageFile->getRealPath(), [
                'folder' => 'products',
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ]);

        return $result['secure_url'];
    }

    private function replaceUploadedProductImage(Product $product, ?UploadedFile $imageFile): ?string
    {
        if ($imageFile === null) {
            return $product->image_url;
        }

        $this->deleteProductImage($product);

        return $this->storeUploadedProductImage($imageFile);
    }

    public function deleteProductImage(Product $product): void
    {
        if (empty($product->image_url)) {
            return;
        }

        if (preg_match('/\/products\/([^.\/]+)(?:\.[a-z]+)?$/', $product->image_url, $matches)) {
            $this->getCloudinary()
                ->uploadApi()
                ->destroy('products/' . $matches[1]);
        }
    }

    private function syncProductSuppliers(Product $product, array $suppliers): void
    {
        $supportsProductSupplierPemodal = $this->supportsProductSupplierPemodalColumn();
        $processedSuppliers = [];
        $existingSuppliers = DB::table('product_supplier')
            ->where('product_id', $product->id)
            ->get()
            ->keyBy(fn ($row) => $row->supplier_id.'-'.
                $row->condition.'-'.
                ($supportsProductSupplierPemodal ? ($row->pemodal_user_id ?? '') : '')
            );

        foreach ($suppliers as $supplier) {
            $key = $supplier['supplier_id'].'-'.
                ($supplier['condition'] ?? 'New').'-'.
                ($supportsProductSupplierPemodal ? ($supplier['pemodal_user_id'] ?? '') : '');

            if (isset($processedSuppliers[$key])) {
                $processedSuppliers[$key]['stock'] += (int) $supplier['stock'];

                continue;
            }

            $processedSuppliers[$key] = [
                'supplier_id' => $supplier['supplier_id'],
                'condition' => $supplier['condition'] ?? 'New',
                'stock' => (int) $supplier['stock'],
                'harga_beli' => (float) $supplier['harga_beli'],
                'harga_jual_manual' => (float) $supplier['harga_jual'],
                'warranty_detail' => $this->specService->nullableTrim($supplier['warranty_detail'] ?? null),
                'pemodal_user_id' => $supportsProductSupplierPemodal
                    ? ($supplier['pemodal_user_id'] ?? null)
                    : null,
            ];
        }

        $activeKeys = array_keys($processedSuppliers);

        foreach ($processedSuppliers as $key => $data) {
            $existing = $existingSuppliers->get($key);

            if ($existing) {
                $updatePayload = [
                    'stock' => $data['stock'],
                    'harga_beli' => $data['harga_beli'],
                    'harga_jual_manual' => $data['harga_jual_manual'],
                    'warranty_detail' => $data['warranty_detail'],
                    'updated_at' => now(),
                ];

                if ($supportsProductSupplierPemodal) {
                    $updatePayload['pemodal_user_id'] = $data['pemodal_user_id'];
                }

                DB::table('product_supplier')
                    ->where('id', $existing->id)
                    ->update($updatePayload);

                continue;
            }

            $insertPayload = [
                'product_id' => $product->id,
                'supplier_id' => $data['supplier_id'],
                'condition' => $data['condition'],
                'stock' => $data['stock'],
                'harga_beli' => $data['harga_beli'],
                'harga_jual_manual' => $data['harga_jual_manual'],
                'warranty_detail' => $data['warranty_detail'],
                'entry_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($supportsProductSupplierPemodal) {
                $insertPayload['pemodal_user_id'] = $data['pemodal_user_id'];
            }

            DB::table('product_supplier')->insert($insertPayload);
        }

        $obsoleteIds = $existingSuppliers
            ->reject(fn ($row, $key) => in_array($key, $activeKeys, true))
            ->pluck('id')
            ->all();

        if (! empty($obsoleteIds)) {
            DB::table('product_supplier')
                ->whereIn('id', $obsoleteIds)
                ->delete();
        }
    }

    private function resolveSupplierPayloads(array $suppliers): array
    {
        return collect($suppliers)
            ->map(function (array $supplier) {
                $mode = $this->resolveSupplierInputMode($supplier);

                if ($mode === 'new') {
                    $name = $this->specService->nullableTrim($supplier['new_supplier_name'] ?? null);
                    $address = $this->specService->nullableTrim($supplier['new_supplier_address'] ?? null);

                    $resolvedSupplier = Supplier::firstOrCreate([
                        'nama_supplier' => $name,
                        'alamat' => $address,
                    ]);

                    if ($resolvedSupplier->wasRecentlyCreated) {
                        $this->forgetProductOptionCaches();
                    }

                    $supplier['supplier_id'] = $resolvedSupplier->id;
                }

                unset($supplier['mode'], $supplier['new_supplier_name'], $supplier['new_supplier_address']);

                return $supplier;
            })
            ->values()
            ->all();
    }

    private function resolveSupplierInputMode(array $supplier): string
    {
        if (($supplier['mode'] ?? null) === 'new') {
            return 'new';
        }

        if (
            $this->specService->nullableTrim($supplier['new_supplier_name'] ?? null) !== null ||
            $this->specService->nullableTrim($supplier['new_supplier_address'] ?? null) !== null
        ) {
            return 'new';
        }

        return 'existing';
    }

    private function supportsProductSupplierPemodalColumn(): bool
    {
        static $supportsProductSupplierPemodal;

        if ($supportsProductSupplierPemodal === null) {
            $supportsProductSupplierPemodal = Schema::hasColumn('product_supplier', 'pemodal_user_id');
        }

        return $supportsProductSupplierPemodal;
    }

    public function forgetProductOptionCaches(): void
    {
        Cache::forget(self::SPEC_OPTIONS_CACHE_KEY);
        Cache::forget('transactions:categories');
    }
}
