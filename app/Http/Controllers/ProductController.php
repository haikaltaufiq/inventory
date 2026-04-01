<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();

        $query = Product::with(['suppliers', 'category'])
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

        return view('products.index', compact('products', 'summary', 'categories'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->orderBy('nama_supplier')->get();

        return view('products.create', [
            'categories' => $categories,
            'suppliers' => $suppliers,
            'formSpecs' => [],
            'additionalSpecs' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProductRequest($request);
        $category = Category::query()->findOrFail($validated['category_id']);

        return DB::transaction(function () use ($request, $validated, $category) {
            $imageUrl = $this->storeProductImage($request);
            $specPayload = $this->buildSpecificationPayload($validated, $category->name);

            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => trim($validated['name']),
                'selling_price' => $validated['selling_price'],
                'warranty' => $this->nullableTrim($validated['warranty'] ?? null),
                'description' => $this->nullableTrim($validated['description'] ?? null),
                'technical_specs' => $this->supportsTechnicalSpecsColumn() ? $specPayload['technical_specs'] : null,
                'image_url' => $imageUrl,
            ]);

            $this->syncProductSpecifications($product, $specPayload['specifications']);
            $this->syncProductSuppliers($product, $validated['suppliers'], (float) $validated['selling_price']);

            return redirect()
                ->route('products.index')
                ->with('success', 'Produk berhasil ditambah');
        });
    }

    public function edit(Product $product)
    {
        $product->load(['suppliers', 'specifications', 'category']);
        [$formSpecs, $additionalSpecs] = $this->extractSpecFormData($product);

        $categories = Category::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->orderBy('nama_supplier')->get();

        return view('products.edit', compact(
            'product',
            'categories',
            'suppliers',
            'formSpecs',
            'additionalSpecs'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProductRequest($request);
        $category = Category::query()->findOrFail($validated['category_id']);

        return DB::transaction(function () use ($request, $validated, $category, $product) {
            $imageUrl = $this->replaceProductImage($request, $product);
            $specPayload = $this->buildSpecificationPayload($validated, $category->name);

            $product->update([
                'category_id' => $validated['category_id'],
                'name' => trim($validated['name']),
                'selling_price' => $validated['selling_price'],
                'warranty' => $this->nullableTrim($validated['warranty'] ?? null),
                'description' => $this->nullableTrim($validated['description'] ?? null),
                'technical_specs' => $this->supportsTechnicalSpecsColumn() ? $specPayload['technical_specs'] : null,
                'image_url' => $imageUrl,
            ]);

            $this->syncProductSpecifications($product, $specPayload['specifications']);
            $this->syncProductSuppliers($product, $validated['suppliers'], (float) $validated['selling_price']);

            return redirect()
                ->route('products.index')
                ->with('success', 'Produk berhasil diupdate');
        });
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus');
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
        $definition = $this->specDefinitionForCategory($category->name);

        if ($definition === null) {
            return response()->json([
                'category_key' => null,
                'fields' => [],
                'options' => [],
            ]);
        }

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
            ->values();

        $options = [];

        foreach ($definition['fields'] as $field) {
            $lookupKeys = collect([$field['key']])
                ->merge($field['lookup_keys'] ?? [])
                ->merge(config('product_specs.compatibility_aliases.' . $field['key'], []))
                ->map(fn($key) => $this->normalizeIdentifier($key))
                ->filter()
                ->unique()
                ->values();

            $values = ProductSpecification::query()
                ->get(['spec_key', 'spec_value'])
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
                ->values();

            $options[$field['key']] = $values;
        }

        return response()->json([
            'category_key' => $definition['key'],
            'fields' => $fields,
            'options' => $options,
        ]);
    }

    private function validateProductRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'selling_price' => 'required|numeric|min:0',
            'warranty' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'specs' => 'nullable|array',
            'specs.*.key' => 'nullable|string|max:100',
            'specs.*.value' => 'nullable|string|max:255',
            'extra_specs' => 'nullable|array',
            'extra_specs.*.key' => 'nullable|string|max:100',
            'extra_specs.*.value' => 'nullable|string|max:255',
            'suppliers' => 'required|array|min:1',
            'suppliers.*.supplier_id' => 'required|exists:suppliers,id',
            'suppliers.*.stock' => 'required|integer|min:0',
            'suppliers.*.harga_beli' => 'required|numeric|min:0',
            'suppliers.*.harga_jual' => 'nullable|numeric|min:0',
            'suppliers.*.condition' => 'required|string|in:New,Used,Refurbished',
        ]);

        $validator->after(function ($validator) use ($request) {
            $category = Category::query()->find($request->input('category_id'));
            $definition = $this->specDefinitionForCategory($category?->name);

            if ($definition === null) {
                return;
            }

            foreach ($definition['fields'] as $field) {
                $isRequired = in_array($field['key'], config('product_specs.strict_keys', []), true);
                $value = $this->nullableTrim(data_get($request->input('specs', []), $field['key'] . '.value'));

                if ($isRequired && $value === null) {
                    $validator->errors()->add(
                        'specs.' . $field['key'] . '.value',
                        $field['label'] . ' wajib diisi untuk kategori ' . ($category?->name ?? 'terpilih') . '.'
                    );
                }
            }
        });

        return $validator->validate();
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

    private function syncProductSuppliers(Product $product, array $suppliers, float $defaultSellingPrice): void
    {
        $product->suppliers()->detach();

        $processedSuppliers = [];

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
                'harga_jual_manual' => isset($supplier['harga_jual']) && $supplier['harga_jual'] !== null && $supplier['harga_jual'] !== ''
                    ? (float) $supplier['harga_jual']
                    : $defaultSellingPrice,
            ];
        }

        foreach ($processedSuppliers as $data) {
            $product->suppliers()->attach($data['supplier_id'], [
                'condition' => $data['condition'],
                'stock' => $data['stock'],
                'harga_beli' => $data['harga_beli'],
                'harga_jual_manual' => $data['harga_jual_manual'],
                'entry_date' => now(),
            ]);
        }
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

    private function storeProductImage(Request $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('products', 'public');

        return Storage::url($path);
    }

    private function replaceProductImage(Request $request, Product $product): ?string
    {
        if (!$request->hasFile('image')) {
            return $product->image_url;
        }

        $path = $request->file('image')->store('products', 'public');
        $imageUrl = Storage::url($path);

        if (!empty($product->image_url)) {
            $oldPath = ltrim(str_replace('/storage/', '', $product->image_url), '/');
            Storage::disk('public')->delete($oldPath);
        }

        return $imageUrl;
    }

    private function supportsTechnicalSpecsColumn(): bool
    {
        static $supportsTechnicalSpecs;

        if ($supportsTechnicalSpecs === null) {
            $supportsTechnicalSpecs = Schema::hasColumn('products', 'technical_specs');
        }

        return $supportsTechnicalSpecs;
    }
}
