@php
    $isEdit = isset($product);
    $pageTitle = $isEdit ? 'Edit Produk' : 'Tambah Produk';
    $pageDescription = $isEdit
        ? 'Update data produk dan spesifikasi kompatibilitas dengan rapi.'
        : 'Input komponen PC, spesifikasi kompatibilitas, dan supplier.';
    $submitLabel = $isEdit ? 'Update Produk' : 'Simpan Produk';
    $formAction = $isEdit ? route('products.update', $product) : route('products.store');

    $formSpecs = old('specs', $formSpecs ?? []);
    $additionalSpecs = old('extra_specs', $additionalSpecs ?? []);
    $supplierRows = old(
        'suppliers',
        $isEdit
            ? $product->suppliers->map(function ($supplier) {
                return [
                    'supplier_id' => $supplier->id,
                    'condition' => $supplier->pivot->condition,
                    'stock' => $supplier->pivot->stock,
                    'harga_beli' => $supplier->pivot->harga_beli,
                    'harga_jual' => $supplier->pivot->harga_jual_manual,
                ];
            })->values()->all()
            : [[
                'supplier_id' => $suppliers->first()?->id,
                'condition' => 'New',
                'stock' => 0,
                'harga_beli' => '',
                'harga_jual' => '',
            ]]
    );

    if ($additionalSpecs === []) {
        $additionalSpecs = [['key' => '', 'value' => '']];
    }

    $compatibilitySpecValues = collect($formSpecs)
        ->mapWithKeys(function ($spec, $key) {
            if (is_array($spec)) {
                return [$key => data_get($spec, 'value')];
            }

            return [$key => $spec];
        })
        ->all();

    $lastExtraSpecIndex = collect(array_keys($additionalSpecs))->max() ?? 0;
    $lastSupplierIndex = collect(array_keys($supplierRows))->max() ?? 0;
@endphp

<div class="px-5">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-800">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $pageDescription }}</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
            <p class="text-sm font-medium text-red-700">Masih ada data yang perlu diperbaiki.</p>
            <ul class="mt-2 space-y-1 text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-800">Informasi Produk</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Nama produk</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', $product->name ?? '') }}"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
                                placeholder="Contoh: Intel Core i5-12400F"
                                required>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-600">Kategori</label>
                                <select
                                    id="category-select"
                                    name="category_id"
                                    class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
                                    required>
                                    <option value="">Pilih kategori</option>
                                    @foreach ($categories as $category)
                                        <option
                                            value="{{ $category->id }}"
                                            data-name="{{ $category->name }}"
                                            @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-600">Harga patokan</label>
                                <input
                                    type="number"
                                    name="selling_price"
                                    value="{{ old('selling_price', $product->selling_price ?? '') }}"
                                    class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
                                    placeholder="1000000"
                                    min="0"
                                    required>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Garansi</label>
                            <input
                                type="text"
                                name="warranty"
                                value="{{ old('warranty', $product->warranty ?? '') }}"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
                                placeholder="Contoh: 3 Tahun Resmi">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Deskripsi</label>
                            <textarea
                                name="description"
                                rows="3"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
                                placeholder="Tambahkan catatan singkat tentang produk ini.">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Foto produk</label>
                            <div class="flex items-start gap-4">
                                @if ($isEdit)
                                    <img
                                        src="{{ $product->image_url ?? asset('assets/no-image.svg') }}"
                                        alt="{{ $product->name }}"
                                        class="h-16 w-16 rounded-xl border border-slate-200 object-cover">
                                @endif

                                <div class="flex-1">
                                    <input
                                        type="file"
                                        name="image"
                                        accept="image/*"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
                                    <p class="mt-1 text-xs text-slate-400">Format JPG, PNG, atau WebP dengan ukuran maksimal 2MB.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Spesifikasi Utama</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Saat kategori dipilih, field spesifikasi inti langsung muncul sesuai kebutuhan kategori tersebut.
                            </p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            Value field mendukung pilih dari referensi lama atau input manual
                        </div>
                    </div>

                    <div id="compatibility-empty-state" class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                        Pilih kategori untuk memuat field spesifikasi yang sesuai.
                    </div>

                    <div id="compatibility-fields" class="mt-5 space-y-4"></div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Spesifikasi Tambahan</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Dipakai untuk atribut lain di luar template kompatibilitas utama.
                            </p>
                        </div>
                        <button
                            type="button"
                            id="add-extra-spec"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                            Tambah baris
                        </button>
                    </div>

                    <div id="extra-specs-container" class="mt-5 space-y-3">
                        @foreach ($additionalSpecs as $index => $extraSpec)
                            <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[0.9fr,1.1fr,auto]">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-600">Key</label>
                                    <input
                                        type="text"
                                        name="extra_specs[{{ $index }}][key]"
                                        value="{{ data_get($extraSpec, 'key') }}"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                        placeholder="Contoh: cores">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-600">Value</label>
                                    <input
                                        type="text"
                                        name="extra_specs[{{ $index }}][value]"
                                        value="{{ data_get($extraSpec, 'value') }}"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                        placeholder="Contoh: 6">
                                </div>
                                <div class="flex items-end">
                                    <button
                                        type="button"
                                        class="remove-extra-spec rounded-xl bg-white px-3 py-2.5 text-sm text-red-500 ring-1 ring-slate-200 transition hover:bg-red-50">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Supplier & Stok</h2>
                            <p class="mt-1 text-sm text-slate-500">Tambahkan sumber stok dan harga beli per supplier.</p>
                        </div>
                        <button
                            type="button"
                            id="add-supplier"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                            Tambah supplier
                        </button>
                    </div>

                    <div id="suppliers-container" class="mt-5 space-y-4">
                        @foreach ($supplierRows as $index => $supplierRow)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 supplier-row" data-index="{{ $index }}">
                                <div class="mb-4 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-700">Supplier #{{ $loop->iteration }}</span>
                                    @if (count($supplierRows) > 1)
                                        <button
                                            type="button"
                                            class="remove-supplier text-sm text-red-500 transition hover:text-red-600">
                                            Hapus
                                        </button>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-sm font-medium text-slate-600">Supplier</label>
                                        <select
                                            name="suppliers[{{ $index }}][supplier_id]"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                            required>
                                            @foreach ($suppliers as $supplier)
                                                <option
                                                    value="{{ $supplier->id }}"
                                                    @selected((string) data_get($supplierRow, 'supplier_id') === (string) $supplier->id)>
                                                    {{ $supplier->nama_supplier }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-600">Kondisi</label>
                                        <select
                                            name="suppliers[{{ $index }}][condition]"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
                                            @foreach (['New' => 'Baru', 'Used' => 'Bekas', 'Refurbished' => 'Refurbished'] as $value => $label)
                                                <option value="{{ $value }}" @selected(data_get($supplierRow, 'condition', 'New') === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-600">Stok</label>
                                        <input
                                            type="number"
                                            name="suppliers[{{ $index }}][stock]"
                                            value="{{ data_get($supplierRow, 'stock', 0) }}"
                                            min="0"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                            required>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-600">Harga beli</label>
                                        <input
                                            type="number"
                                            name="suppliers[{{ $index }}][harga_beli]"
                                            value="{{ data_get($supplierRow, 'harga_beli') }}"
                                            min="0"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                            required>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-600">Harga jual manual</label>
                                        <input
                                            type="number"
                                            name="suppliers[{{ $index }}][harga_jual]"
                                            value="{{ data_get($supplierRow, 'harga_jual') }}"
                                            min="0"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                            placeholder="Kosongkan jika pakai harga patokan">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-5 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-800">Ringkasan Aksi</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Data kompatibilitas akan dinormalisasi otomatis agar referensi socket, RAM type, dan form factor tetap konsisten.
                    </p>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-medium text-slate-700">Kategori utama yang terdeteksi</p>
                        <p class="mt-2 leading-6">
                            Processor, Motherboard, RAM, VGA Card, Power Supply, Casing, dan Storage (SSD/HDD).
                        </p>
                    </div>

                    <div class="mt-5 space-y-3">
                        <button
                            type="submit"
                            class="w-full rounded-xl bg-slate-900 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            {{ $submitLabel }}
                        </button>

                        <a
                            href="{{ route('products.index') }}"
                            class="block w-full rounded-xl bg-slate-100 py-3 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            Batal
                        </a>
                    </div>

                    <div class="mt-6 border-t border-slate-100 pt-4">
                        <p class="text-sm font-medium text-slate-700">Catatan</p>
                        <ul class="mt-2 space-y-2 text-sm text-slate-500">
                            <li>Field wajib hanya aktif untuk key yang dibutuhkan oleh simulasi.</li>
                            <li>Field opsional tetap boleh kosong, tapi disarankan diisi untuk hasil kompatibilitas yang lebih presisi.</li>
                            <li>Nilai referensi akan selalu diambil dari data unik yang sudah pernah disimpan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        (() => {
            const specOptionsUrl = @json(route('products.spec-options'));
            const initialSpecValues = @json($compatibilitySpecValues);
            const specErrors = @json($errors->getMessages());
            const supplierCatalog = @json($suppliers->map(fn($supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->nama_supplier,
            ])->values());

            let compatibilityState = { ...initialSpecValues };
            let extraSpecIndex = {{ $lastExtraSpecIndex + 1 }};
            let supplierIndex = {{ $lastSupplierIndex + 1 }};

            const categorySelect = document.getElementById('category-select');
            const compatibilityFields = document.getElementById('compatibility-fields');
            const compatibilityEmptyState = document.getElementById('compatibility-empty-state');
            const extraSpecsContainer = document.getElementById('extra-specs-container');
            const suppliersContainer = document.getElementById('suppliers-container');
            const addExtraSpecButton = document.getElementById('add-extra-spec');
            const addSupplierButton = document.getElementById('add-supplier');

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function getSpecError(key) {
                const messages = specErrors[`specs.${key}.value`] || [];
                return messages[0] || '';
            }

            function setCompatibilityEmptyState(message, tone = 'neutral') {
                compatibilityEmptyState.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-600');
                compatibilityEmptyState.classList.add('border-dashed', 'border-slate-200', 'bg-slate-50', 'text-slate-500');

                if (tone === 'error') {
                    compatibilityEmptyState.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-500');
                    compatibilityEmptyState.classList.add('border-red-200', 'bg-red-50', 'text-red-600');
                }

                compatibilityEmptyState.textContent = message;
            }

            function updateOptionalHint(input, field) {
                const hintEl = document.querySelector(`[data-spec-hint="${field.key}"]`);

                if (!hintEl) {
                    return;
                }

                if (field.required) {
                    hintEl.className = 'mt-1 text-xs text-slate-400';
                    hintEl.textContent = field.hint || 'Pilih dari referensi yang ada atau ketik value baru jika belum tersedia.';
                    return;
                }

                if ((input.value || '').trim() === '') {
                    hintEl.className = 'mt-1 text-xs text-amber-600';
                    hintEl.textContent = 'Opsional, tapi sebaiknya diisi supaya pencocokan kompatibilitas lebih akurat.';
                    return;
                }

                hintEl.className = 'mt-1 text-xs text-slate-400';
                hintEl.textContent = field.hint || 'Nilai ini akan tersimpan dan muncul lagi sebagai referensi di produk berikutnya.';
            }

            function bindCompatibilityFieldEvents(fields) {
                fields.forEach((field) => {
                    const input = document.querySelector(`[data-spec-input="${field.key}"]`);

                    if (!input) {
                        return;
                    }

                    input.addEventListener('input', () => {
                        compatibilityState[field.key] = input.value;
                        updateOptionalHint(input, field);
                    });

                    updateOptionalHint(input, field);
                });
            }

            function renderCompatibilityFields(fields, options) {
                compatibilityFields.innerHTML = fields.map((field) => {
                    const currentValue = compatibilityState[field.key] || '';
                    const referenceOptions = options[field.key] || [];
                    const currentError = getSpecError(field.key);
                    const listId = `spec-options-${field.key}`;
                    const referenceCount = referenceOptions.length;

                    return `
                        <div class="rounded-2xl border ${currentError ? 'border-red-200 bg-red-50/40' : 'border-slate-200 bg-slate-50/50'} p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-700">${escapeHtml(field.label)}</p>
                                    <p class="mt-1 text-xs text-slate-400">${escapeHtml(field.hint || 'Field ini dipakai untuk kebutuhan kompatibilitas komponen.')}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-xs ${field.required ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500'}">
                                    ${field.required ? 'Required' : 'Optional'}
                                </span>
                            </div>

                            <input type="hidden" name="specs[${escapeHtml(field.key)}][key]" value="${escapeHtml(field.key)}">

                            <div class="mt-4">
                                <label class="mb-1 block text-sm font-medium text-slate-600">Value spesifikasi</label>
                                <input
                                    type="text"
                                    name="specs[${escapeHtml(field.key)}][value]"
                                    value="${escapeHtml(currentValue)}"
                                    data-spec-input="${escapeHtml(field.key)}"
                                    data-spec-reference="${escapeHtml(field.key)}"
                                    list="${escapeHtml(listId)}"
                                    autocomplete="off"
                                    placeholder="${escapeHtml(field.placeholder || '')}"
                                    class="w-full rounded-xl border ${currentError ? 'border-red-300 bg-white' : 'border-slate-200 bg-white'} px-4 py-2.5 text-sm"
                                    ${field.required ? 'required' : ''}>
                                <datalist id="${escapeHtml(listId)}">
                                    ${referenceOptions.map((option) => `<option value="${escapeHtml(option)}"></option>`).join('')}
                                </datalist>
                                <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                                    <p data-spec-hint="${escapeHtml(field.key)}" class="text-xs text-slate-400"></p>
                                    <span class="text-xs text-slate-400">
                                        ${referenceCount > 0
                                            ? `${referenceCount} referensi tersimpan, mulai ketik untuk mencari`
                                            : 'Belum ada referensi, input manual akan tersimpan untuk produk berikutnya'}
                                    </span>
                                </div>
                                ${currentError ? `<p class="mt-1 text-xs text-red-500">${escapeHtml(currentError)}</p>` : ''}
                            </div>
                        </div>
                    `;
                }).join('');

                bindCompatibilityFieldEvents(fields);
            }

            async function loadCompatibilityTemplate() {
                const categoryId = categorySelect.value;
                compatibilityFields.innerHTML = '';

                if (!categoryId) {
                    setCompatibilityEmptyState('Pilih kategori untuk memuat template spesifikasi kompatibilitas.');
                    return;
                }

                setCompatibilityEmptyState('Memuat template kompatibilitas...');

                try {
                    const response = await fetch(`${specOptionsUrl}?category_id=${encodeURIComponent(categoryId)}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const result = await response.json();

                    if (!Array.isArray(result.fields) || result.fields.length === 0) {
                        setCompatibilityEmptyState('Kategori ini belum punya template kompatibilitas khusus. Kamu tetap bisa pakai spesifikasi tambahan di bawah.');
                        return;
                    }

                    compatibilityEmptyState.classList.add('hidden');
                    renderCompatibilityFields(result.fields, result.options || {});
                } catch (error) {
                    setCompatibilityEmptyState('Gagal memuat template kompatibilitas. Coba refresh halaman lalu pilih kategori lagi.', 'error');
                }
            }

            function bindExtraSpecRemoval(scope = document) {
                scope.querySelectorAll('.remove-extra-spec').forEach((button) => {
                    button.addEventListener('click', () => {
                        const row = button.closest('.grid');
                        if (row) {
                            row.remove();
                        }
                    });
                });
            }

            function addExtraSpecRow(values = {}) {
                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[0.9fr,1.1fr,auto]';
                row.innerHTML = `
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Key</label>
                        <input
                            type="text"
                            name="extra_specs[${extraSpecIndex}][key]"
                            value="${escapeHtml(values.key || '')}"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                            placeholder="Contoh: cores">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Value</label>
                        <input
                            type="text"
                            name="extra_specs[${extraSpecIndex}][value]"
                            value="${escapeHtml(values.value || '')}"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                            placeholder="Contoh: 6">
                    </div>
                    <div class="flex items-end">
                        <button
                            type="button"
                            class="remove-extra-spec rounded-xl bg-white px-3 py-2.5 text-sm text-red-500 ring-1 ring-slate-200 transition hover:bg-red-50">
                            Hapus
                        </button>
                    </div>
                `;

                extraSpecsContainer.appendChild(row);
                bindExtraSpecRemoval(row);
                extraSpecIndex += 1;
            }

            function supplierOptionsMarkup(selectedId = '') {
                return supplierCatalog.map((supplier) => `
                    <option value="${supplier.id}" ${String(selectedId) === String(supplier.id) ? 'selected' : ''}>
                        ${escapeHtml(supplier.name)}
                    </option>
                `).join('');
            }

            function bindSupplierRemoval(scope = document) {
                scope.querySelectorAll('.remove-supplier').forEach((button) => {
                    button.addEventListener('click', () => {
                        const rows = suppliersContainer.querySelectorAll('.supplier-row');

                        if (rows.length <= 1) {
                            return;
                        }

                        const row = button.closest('.supplier-row');
                        if (row) {
                            row.remove();
                        }
                    });
                });
            }

            function addSupplierRow(values = {}) {
                const row = document.createElement('div');
                row.className = 'rounded-2xl border border-slate-200 bg-slate-50 p-5 supplier-row';
                row.setAttribute('data-index', supplierIndex);
                row.innerHTML = `
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-700">Supplier tambahan</span>
                        <button type="button" class="remove-supplier text-sm text-red-500 transition hover:text-red-600">
                            Hapus
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-600">Supplier</label>
                            <select
                                name="suppliers[${supplierIndex}][supplier_id]"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                required>
                                ${supplierOptionsMarkup(values.supplier_id || supplierCatalog[0]?.id || '')}
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Kondisi</label>
                            <select
                                name="suppliers[${supplierIndex}][condition]"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
                                <option value="New" ${(values.condition || 'New') === 'New' ? 'selected' : ''}>Baru</option>
                                <option value="Used" ${values.condition === 'Used' ? 'selected' : ''}>Bekas</option>
                                <option value="Refurbished" ${values.condition === 'Refurbished' ? 'selected' : ''}>Refurbished</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Stok</label>
                            <input
                                type="number"
                                name="suppliers[${supplierIndex}][stock]"
                                value="${escapeHtml(values.stock ?? 0)}"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Harga beli</label>
                            <input
                                type="number"
                                name="suppliers[${supplierIndex}][harga_beli]"
                                value="${escapeHtml(values.harga_beli || '')}"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Harga jual manual</label>
                            <input
                                type="number"
                                name="suppliers[${supplierIndex}][harga_jual]"
                                value="${escapeHtml(values.harga_jual || '')}"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                placeholder="Kosongkan jika pakai harga patokan">
                        </div>
                    </div>
                `;

                suppliersContainer.appendChild(row);
                bindSupplierRemoval(row);
                supplierIndex += 1;
            }

            bindExtraSpecRemoval();
            bindSupplierRemoval();
            addExtraSpecButton.addEventListener('click', () => addExtraSpecRow());
            addSupplierButton.addEventListener('click', () => addSupplierRow());
            categorySelect.addEventListener('change', loadCompatibilityTemplate);
            loadCompatibilityTemplate();
        })();
    </script>
@endpush
