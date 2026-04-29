<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductGridService
{
    public function __construct(
        private ProductService $productService,
        private ProductSpecService $specService
    ) {}

    public function processGridSave(Request $request): array
    {
        $rows = $request->input('products', []);

        if (!is_array($rows) || $rows === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada perubahan untuk disimpan.',
            ];
        }

        $operations = [];
        $validationErrors = [];

        foreach ($rows as $clientKey => $row) {
            $row = is_array($row) ? $row : [];
            $productId = data_get($row, 'id');
            $product = !empty($productId) ? Product::query()->findOrFail($productId) : null;
            $isNew = $this->toBoolean(data_get($row, '_is_new')) || empty($productId);
            $isDirty = $this->toBoolean(data_get($row, '_dirty')) || $isNew;
            $markedForDelete = $this->toBoolean(data_get($row, '_delete'));

            if ($markedForDelete) {
                if (!empty($productId)) {
                    $operations[] = [
                        'action' => 'delete',
                        'product' => $product,
                    ];
                }

                continue;
            }

            if (!$isDirty) {
                continue;
            }

            $imageFile = data_get($request->allFiles(), "products.$clientKey.image");
            $payload = $this->mergePersistedRequiredSpecs(
                $this->normalizeGridPayload($row),
                $product
            );

            $validator = $this->makeProductValidator($payload, $imageFile);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $validationErrors["products.$clientKey.$field"] = $messages;
                }

                continue;
            }

            $operations[] = [
                'action' => 'upsert',
                'product' => $product,
                'validated' => $validator->validated(),
                'image' => $imageFile,
            ];
        }

        if ($validationErrors !== []) {
            throw ValidationException::withMessages($validationErrors);
        }

        if ($operations === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada perubahan untuk disimpan.',
            ];
        }

        $savedCount = 0;
        $deletedCount = 0;

        DB::transaction(function () use ($operations, &$savedCount, &$deletedCount) {
            foreach ($operations as $operation) {
                if ($operation['action'] === 'delete') {
                    $this->productService->deleteProductImage($operation['product']);
                    $operation['product']->delete();
                    $deletedCount++;
                    continue;
                }

                $this->productService->persistProduct(
                    $operation['product'],
                    $operation['validated'],
                    $operation['image']
                );

                $savedCount++;
            }
        });

        $this->productService->forgetProductOptionCaches();

        $message = trim(collect([
            $savedCount > 0 ? $savedCount . ' produk disimpan.' : null,
            $deletedCount > 0 ? $deletedCount . ' produk dihapus.' : null,
        ])->filter()->implode(' '));

        return [
            'success' => true,
            'message' => $message !== '' ? $message : 'Perubahan berhasil disimpan.',
        ];
    }

    public function resolveGridRowsForIndex(Collection $products, Collection $categories): array
    {
        $oldProducts = session()->getOldInput('products');

        if (is_array($oldProducts) && $oldProducts !== []) {
            return $this->normalizeOldGridRows($oldProducts, $products, $categories);
        }

        return $products
            ->map(fn(Product $product) => $this->serializeProductForGrid($product))
            ->values()
            ->all();
    }

    private function normalizeOldGridRows(array $rows, Collection $products, Collection $categories): array
    {
        $productMap = $products->keyBy('id');
        $categoryMap = $categories->pluck('name', 'id');

        return collect($rows)
            ->map(function ($row, $clientKey) use ($productMap, $categoryMap) {
                $row = is_array($row) ? $row : [];
                $productId = (int) data_get($row, 'id', 0);
                $product = $productId > 0 ? $productMap->get($productId) : null;
                $categoryId = (string) data_get($row, 'category_id', '');

                $specs = collect(data_get($row, 'specs', []))
                    ->mapWithKeys(function ($spec, $key) {
                        $resolvedKey = $this->specService->nullableTrim(data_get($spec, 'key', is_string($key) ? $key : null));

                        if ($resolvedKey === null) {
                            return [];
                        }

                        return [$resolvedKey => [
                            'key' => $resolvedKey,
                            'value' => (string) data_get($spec, 'value', ''),
                            'mode' => (string) data_get($spec, 'mode', 'existing'),
                        ]];
                    })
                    ->all();

                $additionalSpecs = collect(data_get($row, 'additional_specs', []))
                    ->values()
                    ->map(fn($spec) => [
                        'key' => (string) data_get($spec, 'key', ''),
                        'value' => (string) data_get($spec, 'value', ''),
                    ])
                    ->all();

                $suppliers = collect(data_get($row, 'suppliers', []))
                    ->values()
                    ->map(fn($supplier) => [
                        'mode' => (string) data_get($supplier, 'mode', 'existing'),
                        'supplier_id' => (string) data_get($supplier, 'supplier_id', ''),
                        'pemodal_user_id' => (string) data_get($supplier, 'pemodal_user_id', ''),
                        'new_supplier_name' => (string) data_get($supplier, 'new_supplier_name', ''),
                        'new_supplier_address' => (string) data_get($supplier, 'new_supplier_address', ''),
                        'condition' => (string) data_get($supplier, 'condition', 'New'),
                        'stock' => (string) data_get($supplier, 'stock', '0'),
                        'harga_beli' => (string) data_get($supplier, 'harga_beli', ''),
                        'harga_jual' => (string) data_get($supplier, 'harga_jual', ''),
                        'warranty_detail' => (string) data_get($supplier, 'warranty_detail', ''),
                    ])
                    ->all();

                return [
                    'client_key' => (string) $clientKey,
                    'id' => $productId > 0 ? $productId : null,
                    'name' => (string) data_get($row, 'name', ''),
                    'brand' => (string) data_get($row, 'brand', ''),
                    'category_id' => $categoryId,
                    'category_name' => $categoryMap->get((int) $categoryId, $product?->category?->name ?? 'Pilih kategori'),
                    'letak_barang' => (string) data_get($row, 'letak_barang', ''),
                    'description' => (string) data_get($row, 'description', ''),
                    'image_url' => $product?->image_url,
                    'specs' => $specs,
                    'additional_specs' => $additionalSpecs,
                    'suppliers' => $suppliers,
                    'is_new' => $this->toBoolean(data_get($row, '_is_new')) || $productId === 0,
                    'is_dirty' => $this->toBoolean(data_get($row, '_dirty', true)),
                    'marked_for_delete' => $this->toBoolean(data_get($row, '_delete')),
                    'is_editing' => true,
                ];
            })
            ->values()
            ->all();
    }

    private function serializeProductForGrid(Product $product): array
    {
        $product->loadMissing(['category', 'suppliers', 'specs']);
        [$formSpecs, $additionalSpecs] = $this->specService->extractSpecFormData($product);

        return [
            'client_key' => 'existing_' . $product->id,
            'id' => $product->id,
            'name' => $product->name,
            'brand' => $product->brand ?? '',
            'category_id' => (string) $product->category_id,
            'category_name' => $product->category?->name ?? 'No Category',
            'letak_barang' => $product->letak_barang ?? '',
            'description' => $product->description ?? '',
            'image_url' => $product->image_url,
            'specs' => collect($formSpecs)
                ->mapWithKeys(fn($value, $key) => [$key => ['key' => $key, 'value' => (string) $value]])
                ->all(),
            'additional_specs' => array_values(array_map(function (array $spec) {
                return [
                    'key' => (string) data_get($spec, 'key', ''),
                    'value' => (string) data_get($spec, 'value', ''),
                ];
            }, $additionalSpecs)),
            'suppliers' => $product->suppliers
                ->map(fn($supplier) => [
                    'mode' => 'existing',
                    'supplier_id' => (string) $supplier->id,
                    'pemodal_user_id' => $this->supportsProductSupplierPemodalColumn()
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
            'is_new' => false,
            'is_dirty' => false,
            'marked_for_delete' => false,
            'is_editing' => false,
        ];
    }

    private function normalizeGridPayload(array $row): array
    {
        $specs = collect(data_get($row, 'specs', []))
            ->mapWithKeys(function ($spec, $key) {
                $resolvedKey = $this->specService->nullableTrim(data_get($spec, 'key', is_string($key) ? $key : null));

                if ($resolvedKey === null) {
                    return [];
                }

                return [$resolvedKey => [
                    'key' => $resolvedKey,
                    'value' => data_get($spec, 'value'),
                    'mode' => data_get($spec, 'mode', 'existing'),
                ]];
            })
            ->all();

        return [
            'name' => data_get($row, 'name'),
            'brand' => data_get($row, 'brand'),
            'category_id' => data_get($row, 'category_id'),
            'letak_barang' => data_get($row, 'letak_barang'),
            'description' => data_get($row, 'description'),
            'specs' => $specs,
            'extra_specs' => collect(data_get($row, 'additional_specs', []))
                ->values()
                ->map(fn($spec) => [
                    'key' => data_get($spec, 'key'),
                    'value' => data_get($spec, 'value'),
                ])
                ->all(),
            'suppliers' => collect(data_get($row, 'suppliers', []))
                ->values()
                ->map(fn($supplier) => [
                    'mode' => data_get($supplier, 'mode', 'existing'),
                    'supplier_id' => data_get($supplier, 'supplier_id'),
                    'pemodal_user_id' => data_get($supplier, 'pemodal_user_id'),
                    'new_supplier_name' => data_get($supplier, 'new_supplier_name'),
                    'new_supplier_address' => data_get($supplier, 'new_supplier_address'),
                    'stock' => data_get($supplier, 'stock'),
                    'harga_beli' => data_get($supplier, 'harga_beli'),
                    'harga_jual' => data_get($supplier, 'harga_jual'),
                    'warranty_detail' => data_get($supplier, 'warranty_detail'),
                    'condition' => data_get($supplier, 'condition', 'New'),
                ])
                ->all(),
        ];
    }

    private function mergePersistedRequiredSpecs(array $payload, ?Product $product): array
    {
        if ($product === null) {
            return $payload;
        }

        $product->loadMissing(['category', 'specs']);
        [$formSpecs] = $this->specService->extractSpecFormData($product);

        if ($formSpecs === []) {
            return $payload;
        }

        $definition = $this->specService->specDefinitionForCategory($product->category?->name);

        if ($definition === null) {
            return $payload;
        }

        foreach ($definition['fields'] as $field) {
            if (!in_array($field['key'], config('product_specs.strict_keys', []), true)) {
                continue;
            }

            $currentValue = $this->specService->nullableTrim(data_get($payload, 'specs.' . $field['key'] . '.value'));
            $storedValue = $formSpecs[$field['key']] ?? null;

            if ($currentValue !== null || $storedValue === null) {
                continue;
            }

            data_set($payload, 'specs.' . $field['key'] . '.key', $field['key']);
            data_set($payload, 'specs.' . $field['key'] . '.value', $storedValue);
            data_set($payload, 'specs.' . $field['key'] . '.mode', 'existing');
        }

        return $payload;
    }

    private function makeProductValidator(array $payload, ?\Illuminate\Http\UploadedFile $imageFile = null)
    {
        // For grid logic, we manually instantiate ProductRequest logic.
        $request = new \App\Http\Requests\ProductRequest();
        $request->merge($payload);
        $data = $payload;
        $data['image'] = $imageFile;

        $validator = Validator::make(
            $data,
            $request->rules(),
            $request->messages(),
            $request->attributes()
        );

        $request->withValidator($validator);

        return $validator;
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
