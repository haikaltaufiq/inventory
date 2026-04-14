<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'letak_barang' => 'nullable|string|max:255',
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
            'suppliers.*.pemodal_user_id' => 'nullable|exists:users,id',
            'suppliers.*.new_supplier_name' => 'nullable|string|max:225',
            'suppliers.*.new_supplier_address' => 'nullable|string|max:255',
            'suppliers.*.stock' => 'required|integer|min:0',
            'suppliers.*.harga_beli' => 'required|numeric|min:0',
            'suppliers.*.harga_jual' => 'required|numeric|min:0',
            'suppliers.*.warranty_detail' => 'nullable|string|max:255',
            'suppliers.*.condition' => 'required|string|in:New,Used,Refurbished',
        ];
    }

    public function messages()
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'array' => ':attribute harus berupa daftar.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'exists' => 'Pilihan untuk :attribute tidak valid.',
            'in' => 'Pilihan untuk :attribute tidak valid.',
            'image' => ':attribute harus berupa file gambar.',
            'mimes' => ':attribute harus berformat: :values.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.file' => 'Ukuran :attribute maksimal :max KB.',
            'min.array' => ':attribute minimal :min item.',
            'min.integer' => ':attribute minimal :min.',
            'min.numeric' => ':attribute minimal :min.',
        ];
    }

    public function attributes()
    {
        $payload = $this->all();
        $attributes = [
            'name' => 'nama produk',
            'brand' => 'brand',
            'category_id' => 'kategori produk',
            'letak_barang' => 'letak barang',
            'description' => 'deskripsi',
            'image' => 'foto produk',
            'specs' => 'spesifikasi utama',
            'extra_specs' => 'spesifikasi tambahan',
            'suppliers' => 'data supplier',
        ];

        $category = Category::query()->find($payload['category_id'] ?? null);
        $definition = $this->specDefinitionForCategory($category?->name);

        foreach (($definition['fields'] ?? []) as $field) {
            $attributes['specs.' . $field['key'] . '.key'] = 'kunci ' . Str::lower($field['label']);
            $attributes['specs.' . $field['key'] . '.value'] = Str::lower($field['label']);
            $attributes['specs.' . $field['key'] . '.mode'] = 'mode input ' . Str::lower($field['label']);
        }

        foreach (($payload['extra_specs'] ?? []) as $index => $spec) {
            $prefix = 'spesifikasi tambahan #' . ($index + 1);
            $attributes['extra_specs.' . $index . '.key'] = $prefix . ' - nama field';
            $attributes['extra_specs.' . $index . '.value'] = $prefix . ' - isi field';
        }

        foreach (($payload['suppliers'] ?? []) as $index => $supplier) {
            $prefix = 'supplier #' . ($index + 1);
            $attributes['suppliers.' . $index . '.mode'] = $prefix . ' - mode input';
            $attributes['suppliers.' . $index . '.supplier_id'] = $prefix . ' - supplier';
            $attributes['suppliers.' . $index . '.pemodal_user_id'] = $prefix . ' - pemodal';
            $attributes['suppliers.' . $index . '.new_supplier_name'] = $prefix . ' - nama supplier baru';
            $attributes['suppliers.' . $index . '.new_supplier_address'] = $prefix . ' - alamat supplier baru';
            $attributes['suppliers.' . $index . '.stock'] = $prefix . ' - stok';
            $attributes['suppliers.' . $index . '.harga_beli'] = $prefix . ' - harga beli';
            $attributes['suppliers.' . $index . '.harga_jual'] = $prefix . ' - harga jual';
            $attributes['suppliers.' . $index . '.warranty_detail'] = $prefix . ' - garansi supplier';
            $attributes['suppliers.' . $index . '.condition'] = $prefix . ' - kondisi';
        }

        return $attributes;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $payload = $this->all();
            $supportsProductSupplierPemodal = $this->supportsProductSupplierPemodalColumn();
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
                $pemodalUserId = data_get($supplier, 'pemodal_user_id');
                $supplierReference = null;
                $supplierLabel = 'Supplier #' . ($index + 1);

                if ($supportsProductSupplierPemodal && ($pemodalUserId === null || $pemodalUserId === '')) {
                    $validator->errors()->add(
                        'suppliers.' . $index . '.pemodal_user_id',
                        $supplierLabel . ': pemodal wajib dipilih.'
                    );
                }

                if ($mode === 'new') {
                    $newName = $this->nullableTrim(data_get($supplier, 'new_supplier_name'));
                    $newAddress = $this->nullableTrim(data_get($supplier, 'new_supplier_address'));

                    if ($newName === null) {
                        $validator->errors()->add(
                            'suppliers.' . $index . '.new_supplier_name',
                            $supplierLabel . ': nama supplier baru wajib diisi.'
                        );
                    }

                    if ($newAddress === null) {
                        $validator->errors()->add(
                            'suppliers.' . $index . '.new_supplier_address',
                            $supplierLabel . ': alamat supplier baru wajib diisi.'
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
                            $supplierLabel . ': pilih supplier lama atau gunakan input supplier baru.'
                        );
                    } else {
                        $supplierReference = 'existing:' . $supplierId;
                    }
                }

                if ($supplierReference === null) {
                    continue;
                }

                $key = $supplierReference . '::' . $condition . '::' . ($supportsProductSupplierPemodal ? $pemodalUserId : '');

                if (isset($seenSupplierConditions[$key])) {
                    $validator->errors()->add(
                        'suppliers.' . $index . '.supplier_id',
                        'Supplier #' . ($index + 1) . ': kombinasi supplier dan kondisi harus unik pada satu produk.'
                    );
                }

                $seenSupplierConditions[$key] = true;
            }
        });
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

    private function normalizeIdentifier(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function supportsProductSupplierPemodalColumn(): bool
    {
        static $supportsProductSupplierPemodal;

        if ($supportsProductSupplierPemodal === null) {
            $supportsProductSupplierPemodal = Schema::hasColumn('product_supplier', 'pemodal_user_id');
        }

        return $supportsProductSupplierPemodal;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $trimmed === '' ? null : $trimmed;
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
}
