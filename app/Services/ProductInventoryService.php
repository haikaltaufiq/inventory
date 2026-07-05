<?php

namespace App\Services;

use App\Models\Product;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;

class ProductInventoryService
{
    public function __construct(
        private ProductSpecService $specService
    ) {}

    public function resolveProductRowsForIndex(Collection $products): array
    {
        return $products
            ->map(fn (Product $product) => $this->serializeProductForInventory($product))
            ->values()
            ->all();
    }

    private function serializeProductForInventory(Product $product): array
    {
        $product->loadMissing(['category', 'suppliers', 'specs']);
        [$formSpecs, $additionalSpecs] = $this->specService->extractSpecFormData($product);

        return [
            'client_key' => 'product_'.$product->id,
            'id' => $product->id,
            'name' => $product->name,
            'serial_number' => $product->serial_number ?? '',
            'brand' => $product->brand ?? '',
            'category_id' => (string) $product->category_id,
            'category_name' => $product->category?->name ?? 'No Category',
            'letak_barang' => $product->letak_barang ?? '',
            'description' => $product->description ?? '',
            'image_url' => $product->image_url,
            'specs' => collect($formSpecs)
                ->mapWithKeys(fn ($value, $key) => [$key => [
                    'key' => $key,
                    'value' => (string) $value,
                    'mode' => 'existing',
                ]])
                ->all(),
            'additional_specs' => array_values(array_map(function (array $spec) {
                return [
                    'key' => (string) data_get($spec, 'key', ''),
                    'value' => (string) data_get($spec, 'value', ''),
                ];
            }, $additionalSpecs)),
            'suppliers' => $product->suppliers
                ->map(fn ($supplier) => [
                    'mode' => 'existing',
                    'supplier_id' => (string) $supplier->id,
                    'pemodal_user_id' => SchemaCache::productSupplierHasPemodal()
                        ? (string) ($supplier->pivot->pemodal_user_id ?? '')
                        : '',
                    'new_supplier_name' => '',
                    'new_supplier_address' => '',
                    'condition' => (string) $supplier->pivot->condition,
                    'stock' => (string) $supplier->pivot->stock,
                    'harga_beli' => (string) $supplier->pivot->harga_beli,
                    'harga_jual' => (string) $supplier->pivot->harga_jual_manual,
                    'warranty_detail' => (string) ($supplier->pivot->warranty_detail ?? ''),
                ])
                ->values()
                ->all(),
        ];
    }

}
