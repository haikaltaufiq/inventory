<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->orderBy('nama_supplier')->get();

        $query = Product::with(['suppliers', 'category', 'specifications'])
            ->withSum('suppliers as total_stok_count', 'product_supplier.stock');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $filteredData = (clone $query)->get();

        $summary = [
            'total_produk' => $filteredData->count(),
            'total_stok' => 0,
            'nilai_inv' => 0,
            'stok_menipis' => 0,
        ];

        foreach ($filteredData as $product) {
            foreach ($product->suppliers as $supplier) {
                $stock = $supplier->pivot->stock;
                $summary['total_stok'] += $stock;
                $summary['nilai_inv'] += ($stock * $supplier->pivot->harga_beli);

                if ($stock <= 10) {
                    $summary['stok_menipis']++;
                }
            }
        }

        $products = $query->paginate(10)->withQueryString();

        return view('products.index', [
            'products' => $products,
            'summary' => $summary,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'gridRows' => $this->resolveGridRowsForIndex($products->getCollection(), $categories),
            'specTemplates' => $this->buildAllSpecTemplates($categories),
        ]);
    }

    public function create()
    {
        return redirect()->route('products.index');
    }

    public function store(Request $request)
    {
        $validated = $this->validateProductPayload($request->all(), $request->file('image'));

        DB::transaction(function () use ($validated, $request) {
            $this->persistProduct(null, $validated, $request->file('image'));
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambah.');
    }

    public function edit(Product $product)
    {
        return redirect()->route('products.index');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProductPayload($request->all(), $request->file('image'));

        DB::transaction(function () use ($validated, $request, $product) {
            $this->persistProduct($product, $validated, $request->file('image'));
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diupdate.');
    }

    public function gridSave(Request $request)
    {
        $rows = $request->input('products', []);

        if (!is_array($rows) || $rows === []) {
            return redirect()
                ->back()
                ->with('success', 'Tidak ada perubahan untuk disimpan.');
        }

        $operations = [];
        $validationErrors = [];

        foreach ($rows as $clientKey => $row) {
            $row = is_array($row) ? $row : [];
            $productId = data_get($row, 'id');
            $isNew = $this->toBoolean(data_get($row, '_is_new')) || empty($productId);
            $isDirty = $this->toBoolean(data_get($row, '_dirty')) || $isNew;
            $markedForDelete = $this->toBoolean(data_get($row, '_delete'));

            if ($markedForDelete) {
                if (!empty($productId)) {
                    $operations[] = [
                        'action' => 'delete',
                        'product' => Product::query()->findOrFail($productId),
                    ];
                }

                continue;
            }

            if (!$isDirty) {
                continue;
            }

            $imageFile = data_get($request->allFiles(), "products.$clientKey.image");
            $payload = $this->normalizeGridPayload($row);
            $validator = $this->makeProductValidator($payload, $imageFile);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $validationErrors["products.$clientKey.$field"] = $messages;
                }

                continue;
            }

            $operations[] = [
                'action' => 'upsert',
                'product' => !empty($productId) ? Product::query()->findOrFail($productId) : null,
                'validated' => $validator->validated(),
                'image' => $imageFile,
            ];
        }

        if ($validationErrors !== []) {
            throw ValidationException::withMessages($validationErrors);
        }

        if ($operations === []) {
            return $this->redirectAfterGridSave($request, 'Tidak ada perubahan untuk disimpan.');
        }

        $savedCount = 0;
        $deletedCount = 0;

        DB::transaction(function () use ($operations, &$savedCount, &$deletedCount) {
            foreach ($operations as $operation) {
                if ($operation['action'] === 'delete') {
                    $this->deleteProductImage($operation['product']);
                    $operation['product']->delete();
                    $deletedCount++;
                    continue;
                }

                $this->persistProduct(
                    $operation['product'],
                    $operation['validated'],
                    $operation['image']
                );

                $savedCount++;
            }
        });

        $message = trim(collect([
            $savedCount > 0 ? $savedCount . ' produk disimpan.' : null,
            $deletedCount > 0 ? $deletedCount . ' produk dihapus.' : null,
        ])->filter()->implode(' '));

        return $this->redirectAfterGridSave(
            $request,
            $message !== '' ? $message : 'Perubahan berhasil disimpan.'
        );
    }

    public function destroy(Product $product)
    {
        $this->deleteProductImage($product);
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    public function reportProduct()
    {
        return view('laporan-product.index');
    }

    public function specOptions(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $category = Category::query()->findOrFail($request->integer('category_id'));
        return response()->json($this->buildSpecTemplatePayload($category));
    }

    private function resolveGridRowsForIndex(Collection $products, Collection $categories): array
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
                        $resolvedKey = $this->nullableTrim(data_get($spec, 'key', is_string($key) ? $key : null));

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
                        'new_supplier_name' => (string) data_get($supplier, 'new_supplier_name', ''),
                        'new_supplier_address' => (string) data_get($supplier, 'new_supplier_address', ''),
                        'condition' => (string) data_get($supplier, 'condition', 'New'),
                        'stock' => (string) data_get($supplier, 'stock', '0'),
                        'harga_beli' => (string) data_get($supplier, 'harga_beli', ''),
                        'harga_jual' => (string) data_get($supplier, 'harga_jual', ''),
                    ])
                    ->all();

                return [
                    'client_key' => (string) $clientKey,
                    'id' => $productId > 0 ? $productId : null,
                    'name' => (string) data_get($row, 'name', ''),
                    'category_id' => $categoryId,
                    'category_name' => $categoryMap->get((int) $categoryId, $product?->category?->name ?? 'Pilih kategori'),
                    'warranty' => (string) data_get($row, 'warranty', ''),
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
        $product->loadMissing(['category', 'suppliers', 'specifications']);
        [$formSpecs, $additionalSpecs] = $this->extractSpecFormData($product);

        return [
            'client_key' => 'existing_' . $product->id,
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => (string) $product->category_id,
            'category_name' => $product->category?->name ?? 'No Category',
            'warranty' => $product->warranty ?? '',
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
                    'new_supplier_name' => '',
                    'new_supplier_address' => '',
                    'condition' => (string) $supplier->pivot->condition,
                    'stock' => (string) $supplier->pivot->stock,
                    'harga_beli' => (string) $supplier->pivot->harga_beli,
                    'harga_jual' => (string) $supplier->pivot->harga_jual_manual,
                ])
                ->values()
                ->all(),
            'is_new' => false,
            'is_dirty' => false,
            'marked_for_delete' => false,
            'is_editing' => false,
        ];
    }

    private function buildAllSpecTemplates(Collection $categories): array
    {
        $allSpecifications = ProductSpecification::query()->get(['spec_key', 'spec_value']);

        return $categories
            ->mapWithKeys(fn(Category $category) => [
                $category->id => $this->buildSpecTemplatePayload($category, $allSpecifications),
            ])
            ->all();
    }

    private function buildSpecTemplatePayload(?Category $category, ?Collection $allSpecifications = null): array
    {
        $definition = $this->specDefinitionForCategory($category?->name);

        if ($definition === null) {
            return [
                'category_key' => null,
                'fields' => [],
                'options' => [],
            ];
        }

        $allSpecifications ??= ProductSpecification::query()->get(['spec_key', 'spec_value']);

        $fields = collect($definition['fields'])
            ->map(function (array $field) {
                return [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'placeholder' => $field['placeholder'] ?? '',
                    'hint' => $field['hint'] ?? null,
                    'required' => in_array($field['key'], config('product_specs.strict_keys', []), true),
                ];
            })
            ->values()
            ->all();

        $options = [];

        foreach ($definition['fields'] as $field) {
            $lookupKeys = collect([$field['key']])
                ->merge($field['lookup_keys'] ?? [])
                ->merge(config('product_specs.compatibility_aliases.' . $field['key'], []))
                ->map(fn($key) => $this->normalizeIdentifier($key))
                ->filter()
                ->unique()
                ->values();

            $options[$field['key']] = $allSpecifications
                ->filter(function (ProductSpecification $specification) use ($lookupKeys) {
                    return $lookupKeys->contains($this->normalizeIdentifier($specification->spec_key))
                        && $this->nullableTrim($specification->spec_value) !== null;
                })
                ->map(fn(ProductSpecification $specification) => $this->normalizeSpecValue(
                    $field['key'],
                    $specification->spec_value
                ))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        return [
            'category_key' => $definition['key'],
            'fields' => $fields,
            'options' => $options,
        ];
    }

    private function normalizeGridPayload(array $row): array
    {
        $specs = collect(data_get($row, 'specs', []))
            ->mapWithKeys(function ($spec, $key) {
                $resolvedKey = $this->nullableTrim(data_get($spec, 'key', is_string($key) ? $key : null));

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
            'category_id' => data_get($row, 'category_id'),
            'warranty' => data_get($row, 'warranty'),
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
                    'new_supplier_name' => data_get($supplier, 'new_supplier_name'),
                    'new_supplier_address' => data_get($supplier, 'new_supplier_address'),
                    'stock' => data_get($supplier, 'stock'),
                    'harga_beli' => data_get($supplier, 'harga_beli'),
                    'harga_jual' => data_get($supplier, 'harga_jual'),
                    'condition' => data_get($supplier, 'condition', 'New'),
                ])
                ->all(),
        ];
    }

    private function validateProductPayload(array $payload, ?UploadedFile $imageFile = null): array
    {
        return $this->makeProductValidator($payload, $imageFile)->validate();
    }

    private function makeProductValidator(array $payload, ?UploadedFile $imageFile = null)
    {
        $data = $payload;
        $data['image'] = $imageFile;

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'warranty' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'specs' => 'nullable|array',
            'specs.*.key' => 'nullable|string|max:100',
            'specs.*.value' => 'nullable|string|max:255',
            'specs.*.mode' => 'nullable|string|in:existing,new',
            'extra_specs' => 'nullable|array',
            'extra_specs.*.key' => 'nullable|string|max:100',
            'extra_specs.*.value' => 'nullable|string|max:255',
            'suppliers' => 'required|array|min:1',
            'suppliers.*.mode' => 'nullable|string|in:existing,new',
            'suppliers.*.supplier_id' => 'nullable|exists:suppliers,id',
            'suppliers.*.new_supplier_name' => 'nullable|string|max:225',
            'suppliers.*.new_supplier_address' => 'nullable|string|max:255',
            'suppliers.*.stock' => 'required|integer|min:0',
            'suppliers.*.harga_beli' => 'required|numeric|min:0',
            'suppliers.*.harga_jual' => 'required|numeric|min:0',
            'suppliers.*.condition' => 'required|string|in:New,Used,Refurbished',
        ]);

        $validator->after(function ($validator) use ($payload) {
            $category = Category::query()->find($payload['category_id'] ?? null);
            $definition = $this->specDefinitionForCategory($category?->name);

            if ($definition !== null) {
                foreach ($definition['fields'] as $field) {
                    $isRequired = in_array($field['key'], config('product_specs.strict_keys', []), true);
                    $value = $this->nullableTrim(data_get($payload['specs'] ?? [], $field['key'] . '.value'));

                    if ($isRequired && $value === null) {
                        $validator->errors()->add(
                            'specs.' . $field['key'] . '.value',
                            $field['label'] . ' wajib diisi untuk kategori ' . ($category?->name ?? 'terpilih') . '.'
                        );
                    }
                }
            }

            $seenSupplierConditions = [];

            foreach (($payload['suppliers'] ?? []) as $index => $supplier) {
                $mode = $this->resolveSupplierInputMode($supplier);
                $condition = data_get($supplier, 'condition', 'New');
                $supplierReference = null;

                if ($mode === 'new') {
                    $newName = $this->nullableTrim(data_get($supplier, 'new_supplier_name'));
                    $newAddress = $this->nullableTrim(data_get($supplier, 'new_supplier_address'));

                    if ($newName === null) {
                        $validator->errors()->add(
                            'suppliers.' . $index . '.new_supplier_name',
                            'Nama supplier baru wajib diisi.'
                        );
                    }

                    if ($newAddress === null) {
                        $validator->errors()->add(
                            'suppliers.' . $index . '.new_supplier_address',
                            'Alamat supplier baru wajib diisi.'
                        );
                    }

                    if ($newName !== null && $newAddress !== null) {
                        $matchedSupplierId = Supplier::query()
                            ->where('nama_supplier', $newName)
                            ->where('alamat', $newAddress)
                            ->value('id');

                        $supplierReference = $matchedSupplierId !== null
                            ? 'existing:' . $matchedSupplierId
                            : 'new:' . $this->normalizeIdentifier($newName) . '::' . $this->normalizeIdentifier($newAddress);
                    }
                } else {
                    $supplierId = data_get($supplier, 'supplier_id');

                    if ($supplierId === null || $supplierId === '') {
                        $validator->errors()->add(
                            'suppliers.' . $index . '.supplier_id',
                            'Pilih supplier lama atau gunakan input supplier baru.'
                        );
                    } else {
                        $supplierReference = 'existing:' . $supplierId;
                    }
                }

                if ($supplierReference === null) {
                    continue;
                }

                $key = $supplierReference . '::' . $condition;

                if (isset($seenSupplierConditions[$key])) {
                    $validator->errors()->add(
                        'suppliers.' . $index . '.supplier_id',
                        'Kombinasi supplier dan kondisi harus unik pada satu produk.'
                    );
                }

                $seenSupplierConditions[$key] = true;
            }
        });

        return $validator;
    }

    private function persistProduct(?Product $product, array $validated, ?UploadedFile $imageFile = null): Product
    {
        $category = Category::query()->findOrFail($validated['category_id']);
        $specPayload = $this->buildSpecificationPayload($validated, $category->name);
        $resolvedSuppliers = $this->resolveSupplierPayloads($validated['suppliers']);

        $attributes = [
            'category_id' => $validated['category_id'],
            'name' => trim($validated['name']),
            'warranty' => $this->nullableTrim($validated['warranty'] ?? null),
            'description' => $this->nullableTrim($validated['description'] ?? null),
            'technical_specs' => $this->supportsTechnicalSpecsColumn() ? $specPayload['technical_specs'] : null,
        ];

        if ($product === null) {
            $attributes['image_url'] = $this->storeUploadedProductImage($imageFile);
            $product = Product::create($attributes);
        } else {
            $attributes['image_url'] = $this->replaceUploadedProductImage($product, $imageFile);
            $product->update($attributes);
        }

        $this->syncProductSpecifications($product, $specPayload['specifications']);
        $this->syncProductSuppliers($product, $resolvedSuppliers);

        return $product;
    }

    private function redirectAfterGridSave(Request $request, string $message)
    {
        $redirectTo = $request->string('redirect_to')->toString();

        if (
            $redirectTo !== ''
            && (str_starts_with($redirectTo, url('/')) || str_starts_with($redirectTo, '/'))
        ) {
            return redirect()->to($redirectTo)->with('success', $message);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    private function buildSpecificationPayload(array $validated, string $categoryName): array
    {
        $definition = $this->specDefinitionForCategory($categoryName);
        $baseSpecs = collect();

        if ($definition !== null) {
            foreach ($definition['fields'] as $field) {
                $value = $this->normalizeSpecValue(
                    $field['key'],
                    data_get($validated['specs'] ?? [], $field['key'] . '.value')
                );

                if ($value === null) {
                    continue;
                }

                $baseSpecs->put($field['key'], $value);
            }
        }

        $extraSpecs = collect($validated['extra_specs'] ?? [])
            ->mapWithKeys(function (array $spec) {
                $key = $this->normalizeCustomSpecKey($spec['key'] ?? null);
                $value = $this->normalizeSpecValue($key ?? 'custom', $spec['value'] ?? null);

                if ($key === null || $value === null) {
                    return [];
                }

                return [$key => $value];
            });

        $technicalSpecs = $baseSpecs
            ->merge($extraSpecs)
            ->all();

        $compatibilityAliases = collect(config('product_specs.compatibility_aliases', []))
            ->flatMap(function (array $aliases, string $sourceKey) use ($baseSpecs) {
                if (!$baseSpecs->has($sourceKey)) {
                    return [];
                }

                return collect($aliases)->mapWithKeys(function (string $alias) use ($baseSpecs, $sourceKey) {
                    return [$alias => $baseSpecs->get($sourceKey)];
                });
            });

        $specifications = collect($technicalSpecs)
            ->merge($compatibilityAliases)
            ->map(function ($value, $key) {
                return [
                    'spec_key' => $key,
                    'spec_value' => $value,
                ];
            })
            ->values()
            ->all();

        return [
            'technical_specs' => $technicalSpecs,
            'specifications' => $specifications,
        ];
    }

    private function syncProductSpecifications(Product $product, array $specifications): void
    {
        $product->specifications()->delete();

        foreach ($specifications as $specification) {
            $product->specifications()->create($specification);
        }
    }

    private function syncProductSuppliers(Product $product, array $suppliers): void
    {
        $processedSuppliers = [];
        $existingSuppliers = DB::table('product_supplier')
            ->where('product_id', $product->id)
            ->get()
            ->keyBy(fn($row) => $row->supplier_id . '-' . $row->condition);

        foreach ($suppliers as $supplier) {
            $key = $supplier['supplier_id'] . '-' . ($supplier['condition'] ?? 'New');

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
            ];
        }

        $activeKeys = array_keys($processedSuppliers);

        foreach ($processedSuppliers as $key => $data) {
            $existing = $existingSuppliers->get($key);

            if ($existing) {
                DB::table('product_supplier')
                    ->where('id', $existing->id)
                    ->update([
                        'stock' => $data['stock'],
                        'harga_beli' => $data['harga_beli'],
                        'harga_jual_manual' => $data['harga_jual_manual'],
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('product_supplier')->insert([
                'product_id' => $product->id,
                'supplier_id' => $data['supplier_id'],
                'condition' => $data['condition'],
                'stock' => $data['stock'],
                'harga_beli' => $data['harga_beli'],
                'harga_jual_manual' => $data['harga_jual_manual'],
                'entry_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $obsoleteIds = $existingSuppliers
            ->reject(fn($row, $key) => in_array($key, $activeKeys, true))
            ->pluck('id')
            ->all();

        if (!empty($obsoleteIds)) {
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
                    $name = $this->nullableTrim($supplier['new_supplier_name'] ?? null);
                    $address = $this->nullableTrim($supplier['new_supplier_address'] ?? null);

                    $resolvedSupplier = Supplier::firstOrCreate([
                        'nama_supplier' => $name,
                        'alamat' => $address,
                    ]);

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
            $this->nullableTrim($supplier['new_supplier_name'] ?? null) !== null
            || $this->nullableTrim($supplier['new_supplier_address'] ?? null) !== null
        ) {
            return 'new';
        }

        return 'existing';
    }

    private function extractSpecFormData(Product $product): array
    {
        $definition = $this->specDefinitionForCategory($product->category?->name);
        $rawSpecs = collect($product->technical_specs ?? []);

        if ($rawSpecs->isEmpty()) {
            $rawSpecs = $product->specifications
                ->mapWithKeys(function (ProductSpecification $specification) {
                    return [$specification->spec_key => $specification->spec_value];
                });
        }

        $formSpecs = [];
        $additionalSpecs = [];

        foreach ($rawSpecs as $key => $value) {
            $normalizedValue = $this->nullableTrim($value);

            if ($normalizedValue === null) {
                continue;
            }

            $canonicalKey = $this->canonicalSpecKey($key, $definition);

            if ($canonicalKey !== null) {
                $formSpecs[$canonicalKey] = $normalizedValue;
                continue;
            }

            if ($this->isCompatibilityAliasKey($key)) {
                continue;
            }

            $additionalSpecs[] = [
                'key' => $this->displaySpecKey($key),
                'value' => $normalizedValue,
            ];
        }

        return [$formSpecs, $additionalSpecs];
    }

    private function specDefinitionForCategory(?string $categoryName): ?array
    {
        if ($categoryName === null) {
            return null;
        }

        $normalizedCategory = $this->normalizeIdentifier($categoryName);

        foreach (config('product_specs.categories', []) as $configKey => $definition) {
            $labels = collect($definition['labels'] ?? [])
                ->prepend($configKey)
                ->map(fn($label) => $this->normalizeIdentifier($label));

            if ($labels->contains($normalizedCategory)) {
                return [
                    'key' => $configKey,
                    'fields' => $definition['fields'] ?? [],
                ];
            }
        }

        return null;
    }

    private function canonicalSpecKey(string $key, ?array $definition = null): ?string
    {
        $normalizedKey = $this->normalizeIdentifier($key);
        $definitions = $definition !== null
            ? [$definition]
            : array_map(function ($configKey, $configDefinition) {
                return [
                    'key' => $configKey,
                    'fields' => $configDefinition['fields'] ?? [],
                ];
            }, array_keys(config('product_specs.categories', [])), config('product_specs.categories', []));

        foreach ($definitions as $currentDefinition) {
            foreach ($currentDefinition['fields'] ?? [] as $field) {
                $lookupKeys = collect([$field['key']])
                    ->merge($field['lookup_keys'] ?? [])
                    ->map(fn($item) => $this->normalizeIdentifier($item))
                    ->filter()
                    ->unique();

                if ($lookupKeys->contains($normalizedKey)) {
                    return $field['key'];
                }
            }
        }

        return null;
    }

    private function isCompatibilityAliasKey(string $key): bool
    {
        $normalizedKey = $this->normalizeIdentifier($key);

        return collect(config('product_specs.compatibility_aliases', []))
            ->flatten()
            ->map(fn($alias) => $this->normalizeIdentifier($alias))
            ->contains($normalizedKey);
    }

    private function normalizeSpecValue(string $key, mixed $value): ?string
    {
        $trimmed = $this->nullableTrim($value);

        if ($trimmed === null) {
            return null;
        }

        $matchedExisting = $this->matchExistingSpecValue($key, $trimmed);

        if ($matchedExisting !== null) {
            return $matchedExisting;
        }

        return Str::upper($trimmed);
    }

    private function normalizeCustomSpecKey(?string $key): ?string
    {
        $trimmed = $this->nullableTrim($key);

        if ($trimmed === null) {
            return null;
        }

        $normalized = $this->normalizeIdentifier($trimmed);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeIdentifier(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function normalizeComparableValue(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function matchExistingSpecValue(string $key, string $value): ?string
    {
        static $allSpecifications;

        $allSpecifications ??= ProductSpecification::query()->get(['spec_key', 'spec_value']);

        $normalizedLookupKeys = $this->lookupKeysForSpecField($key)
            ->map(fn($item) => $this->normalizeIdentifier((string) $item))
            ->filter()
            ->unique()
            ->values();

        $comparableValue = $this->normalizeComparableValue($value);

        if ($comparableValue === '') {
            return null;
        }

        return $allSpecifications
            ->first(function (ProductSpecification $specification) use ($normalizedLookupKeys, $comparableValue) {
                return $normalizedLookupKeys->contains($this->normalizeIdentifier($specification->spec_key))
                    && $this->normalizeComparableValue((string) $specification->spec_value) === $comparableValue;
            })
            ?->spec_value;
    }

    private function lookupKeysForSpecField(string $key): Collection
    {
        $lookupKeys = collect([$key])
            ->merge(config('product_specs.compatibility_aliases.' . $key, []));

        foreach (config('product_specs.categories', []) as $definition) {
            foreach (($definition['fields'] ?? []) as $field) {
                if (($field['key'] ?? null) !== $key) {
                    continue;
                }

                $lookupKeys = $lookupKeys
                    ->merge($field['lookup_keys'] ?? [])
                    ->merge(config('product_specs.compatibility_aliases.' . $field['key'], []));
            }
        }

        return $lookupKeys;
    }

    private function displaySpecKey(string $key): string
    {
        return Str::of($key)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $trimmed === '' ? null : $trimmed;
    }

    private function storeUploadedProductImage(?UploadedFile $imageFile): ?string
    {
        if ($imageFile === null) {
            return null;
        }

        $path = $imageFile->store('products', 'public');

        return Storage::url($path);
    }

    private function replaceUploadedProductImage(Product $product, ?UploadedFile $imageFile): ?string
    {
        if ($imageFile === null) {
            return $product->image_url;
        }

        $path = $imageFile->store('products', 'public');
        $imageUrl = Storage::url($path);

        if (!empty($product->image_url)) {
            $oldPath = ltrim(str_replace('/storage/', '', $product->image_url), '/');
            Storage::disk('public')->delete($oldPath);
        }

        return $imageUrl;
    }

    private function deleteProductImage(Product $product): void
    {
        if (empty($product->image_url)) {
            return;
        }

        $oldPath = ltrim(str_replace('/storage/', '', $product->image_url), '/');
        Storage::disk('public')->delete($oldPath);
    }

    private function supportsTechnicalSpecsColumn(): bool
    {
        static $supportsTechnicalSpecs;

        if ($supportsTechnicalSpecs === null) {
            $supportsTechnicalSpecs = Schema::hasColumn('products', 'technical_specs');
        }

        return $supportsTechnicalSpecs;
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
