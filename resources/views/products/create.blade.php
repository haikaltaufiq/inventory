@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
<div class="px-5">
    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-800">Tambah Produk</h1>
        <p class="text-sm text-slate-500">Input komponen PC, spek kompatibilitas, dan supplier.</p>
    </div>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- KIRI: INFORMASI UTAMA --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- INFORMASI PRODUK --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Informasi Produk</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Nama Produk</label>
                            <input type="text" name="name"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm"
                                placeholder="Contoh: Intel Core i5-12400F" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Kategori</label>
                                {{-- Tambahkan id="category-select" dan onchange="updateSpecKeys()" --}}
                                <select name="category_id" id="category-select" onchange="updateSpecKeys()"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" data-name="{{ $cat->name }}">
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Harga Patokan (Baru)</label>
                                <input type="number" name="selling_price"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm"
                                    placeholder="1000000" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Garansi</label>
                            <input type="text" name="warranty"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm"
                                placeholder="Contoh: 3 Tahun Resmi">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Deskripsi</label>
                            <textarea name="description" rows="3"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Foto Produk</label>
                            <input type="file" name="image" accept="image/*"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
                            @error('image')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG, WebP (maks 2MB).</p>
                        </div>
                    </div>
                </div>

                {{-- SPESIFIKASI TEKNIS --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-lg font-semibold text-slate-800">Spesifikasi Teknis</h2>
                        <button type="button" onclick="addSpec()"
                            class="text-sm font-medium text-blue-600 hover:underline">+ Tambah Spek</button>
                    </div>
                    <p class="text-xs text-slate-400 mb-4">
                        Pilih kategori terlebih dahulu agar spec key otomatis muncul.
                    </p>

                    <div id="specs-container" class="space-y-3">
                        {{-- Baris spec pertama --}}
                        <div class="flex gap-3 spec-row">
                            <select name="specs[0][key]"
                                class="spec-key-select w-1/3 px-4 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                                <option value="">Pilih Key</option>
                            </select>
                            <input type="text" name="specs[0][value]" placeholder="Contoh: LGA1700"
                                class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                            <button type="button" onclick="this.parentElement.remove()"
                                class="text-red-500 px-2">×</button>
                        </div>
                    </div>
                </div>

                {{-- SUPPLIER & STOK --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">Supplier & Stok</h2>
                        <button type="button" onclick="addSupplier()"
                            class="px-4 py-2 bg-slate-900 text-white text-xs rounded-xl hover:bg-slate-800">
                            + Tambah Supplier
                        </button>
                    </div>
                    <div id="suppliers-container" class="space-y-4">
                        <div class="supplier-row bg-slate-50 border border-slate-200 rounded-2xl p-5" data-index="0">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-sm font-semibold text-slate-700">Supplier #1</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Supplier</label>
                                    <select name="suppliers[0][supplier_id]"
                                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" required>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Kondisi</label>
                                    <select name="suppliers[0][condition]"
                                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                        <option value="New">Baru</option>
                                        <option value="Used">Bekas</option>
                                        <option value="Refurbished">Refurbished</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Stok</label>
                                    <input type="number" name="suppliers[0][stock]" value="0"
                                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Harga Beli</label>
                                    <input type="number" name="suppliers[0][harga_beli]" placeholder="Rp"
                                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Harga Jual
                                        (Opsional)</label>
                                    <input type="number" name="suppliers[0][harga_jual]"
                                        placeholder="Kosongkan jika pakai harga patokan"
                                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- KANAN: AKSI --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-5">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Aksi</h2>
                    <p class="text-xs text-slate-500 mb-6">
                        Pastikan semua data spek sudah benar untuk fitur kompatibilitas PC Builder.
                    </p>
                    <div class="space-y-3">
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">
                            Simpan Produk
                        </button>
                        <a href="{{ route('products.index') }}"
                            class="block w-full py-3 rounded-xl bg-slate-100 text-slate-700 text-sm text-center hover:bg-slate-200 transition">
                            Batal
                        </a>
                    </div>

                    {{-- PANDUAN SPEC KEY --}}
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-2">Panduan Spec Key</p>
                        <div id="spec-guide" class="text-xs text-slate-400 space-y-1">
                            <p>Pilih kategori untuk melihat panduan spec.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
    // =============================================
    // SPEC KEY MAP — sesuaikan nama kategori kamu
    // =============================================
    const specKeyMap = {
        'Processor': {
            keys: ['socket', 'tdp', 'cores', 'threads', 'base_clock', 'boost_clock'],
            guide: ['socket: LGA1700 / AM5 / AM4', 'tdp: dalam Watt (misal: 125)', 'cores: jumlah core (misal: 8)']
        },
        'Motherboard': {
            keys: ['socket', 'ram_type', 'form_factor', 'ram_slots', 'max_ram', 'pcie_version'],
            guide: ['socket: LGA1700 / AM5 / AM4', 'ram_type: DDR4 / DDR5', 'form_factor: ATX / mATX / ITX']
        },
        'RAM': {
            keys: ['ram_type', 'capacity', 'speed', 'latency', 'kit'],
            guide: ['ram_type: DDR4 / DDR5', 'capacity: dalam GB (misal: 32)', 'speed: dalam MHz (misal: 3200)']
        },
        'VGA': {
            keys: ['tdp', 'vram', 'vram_type', 'pcie_version', 'length'],
            guide: ['tdp: dalam Watt (misal: 200)', 'vram: dalam GB (misal: 12)', 'vram_type: GDDR6 / GDDR6X']
        },
        'Storage': {
            keys: ['interface', 'capacity', 'read_speed', 'write_speed', 'form_factor'],
            guide: ['interface: NVMe / SATA / PCIe', 'capacity: dalam GB atau TB', 'form_factor: M.2 / 2.5"']
        },
        'Power Supply': {
            keys: ['wattage', 'efficiency', 'modular', 'form_factor'],
            guide: ['wattage: dalam Watt (misal: 850)', 'efficiency: 80+ Bronze / Gold / Platinum', 'modular: Full / Semi / Non']
        },
        'Casing': {
            keys: ['form_factor', 'max_gpu_length', 'max_cpu_cooler_height'],
            guide: ['form_factor: ATX / mATX / ITX', 'max_gpu_length: dalam mm', 'max_cpu_cooler_height: dalam mm']
        },
        'CPU Cooler': {
            keys: ['socket', 'height', 'tdp_support', 'type'],
            guide: ['socket: LGA1700 / AM5 / multi-socket', 'tdp_support: dalam Watt', 'type: Air / AIO']
        },
    };

    let specIndex = 1;
    let supplierIndex = 1;

    // Ambil nama kategori yang dipilih
    function getSelectedCategoryName() {
        const select = document.getElementById('category-select');
        const selected = select.options[select.selectedIndex];
        return selected ? selected.getAttribute('data-name') : '';
    }

    // Build option HTML untuk spec key
    function buildSpecOptions(selectedValue = '') {
        const catName = getSelectedCategoryName();
        const keys = specKeyMap[catName]?.keys || [];

        let options = '<option value="">Pilih Key</option>';
        keys.forEach(k => {
            const sel = k === selectedValue ? 'selected' : '';
            options += `<option value="${k}" ${sel}>${k}</option>`;
        });

        // Kalau tidak ada di map, tambahkan opsi custom
        if (keys.length === 0) {
            options = '<option value="">-- Pilih Kategori Dulu --</option>';
        }

        return options;
    }

    // Update semua spec key dropdown yang ada
    function updateSpecKeys() {
        const selects = document.querySelectorAll('.spec-key-select');
        selects.forEach(sel => {
            const currentVal = sel.value;
            sel.innerHTML = buildSpecOptions(currentVal);
        });

        // Update panduan
        updateGuide();
    }

    // Update panduan di sidebar
    function updateGuide() {
        const catName = getSelectedCategoryName();
        const guide = specKeyMap[catName]?.guide || [];
        const guideEl = document.getElementById('spec-guide');

        if (guide.length === 0) {
            guideEl.innerHTML = '<p>Kategori ini belum punya panduan spec.</p>';
            return;
        }

        guideEl.innerHTML = guide.map(g => `<p>• ${g}</p>`).join('');
    }

    // Tambah baris spec baru
    function addSpec() {
        const container = document.getElementById('specs-container');
        container.insertAdjacentHTML('beforeend', `
            <div class="flex gap-3 spec-row">
                <select name="specs[${specIndex}][key]"
                    class="spec-key-select w-1/3 px-4 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    ${buildSpecOptions()}
                </select>
                <input type="text" name="specs[${specIndex}][value]" placeholder="Value"
                    class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 px-2">×</button>
            </div>
        `);
        specIndex++;
    }

    // Tambah supplier baru
    function addSupplier() {
        const container = document.getElementById('suppliers-container');
        container.insertAdjacentHTML('beforeend', `
            <div class="supplier-row bg-slate-50 border border-slate-200 rounded-2xl p-5 mt-4" data-index="${supplierIndex}">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm font-semibold text-slate-700">Supplier #${supplierIndex + 1}</span>
                    <button type="button" onclick="this.closest('.supplier-row').remove()"
                        class="text-red-500 text-xs hover:underline">Hapus</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-600 mb-1">Supplier</label>
                        <select name="suppliers[${supplierIndex}][supplier_id]"
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" required>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Kondisi</label>
                        <select name="suppliers[${supplierIndex}][condition]"
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                            <option value="New">Baru</option>
                            <option value="Used">Bekas</option>
                            <option value="Refurbished">Refurbished</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Stok</label>
                        <input type="number" name="suppliers[${supplierIndex}][stock]" value="0"
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Harga Beli</label>
                        <input type="number" name="suppliers[${supplierIndex}][harga_beli]" placeholder="Rp"
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Harga Jual (Opsional)</label>
                        <input type="number" name="suppliers[${supplierIndex}][harga_jual]"
                            placeholder="Kosongkan jika pakai harga patokan"
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                    </div>
                </div>
            </div>
        `);
        supplierIndex++;
    }
</script>
@endsection
