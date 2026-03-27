@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="px-5">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-800">Edit Produk</h1>
        <p class="text-sm text-slate-500">Update data {{ $product->name }} dengan teliti.</p>
    </div>

    <form action="{{ route('products.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- KIRI --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- INFORMASI PRODUK --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Informasi Produk</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Nama Produk</label>
                            <input type="text" name="name" value="{{ $product->name }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Kategori</label>
                                {{-- Tambah id dan onchange --}}
                                <select name="category_id" id="category-select" onchange="updateSpecKeys()"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" required>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            data-name="{{ $cat->name }}"
                                            {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Harga Patokan (Baru)</label>
                                <input type="number" name="selling_price" value="{{ $product->selling_price }}"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Garansi</label>
                            <input type="text" name="warranty" value="{{ $product->warranty }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Deskripsi</label>
                            <textarea name="description" rows="3"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">{{ $product->description }}</textarea>
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
                        Spec key sudah otomatis menyesuaikan kategori produk.
                    </p>

                    <div id="specs-container" class="space-y-3">
                        @forelse($product->specifications as $index => $spec)
                            <div class="flex gap-3 spec-row">
                                {{-- Dropdown — value dari DB sudah pre-selected --}}
                                <select name="specs[{{ $index }}][key]"
                                    class="spec-key-select w-1/3 px-4 py-2 rounded-xl border border-slate-200 text-sm bg-white"
                                    data-selected="{{ $spec->spec_key }}">
                                    <option value="">Pilih Key</option>
                                </select>
                                <input type="text" name="specs[{{ $index }}][value]"
                                    value="{{ $spec->spec_value }}" placeholder="Value"
                                    class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                <button type="button" onclick="this.parentElement.remove()"
                                    class="text-red-500 px-2">×</button>
                            </div>
                        @empty
                            <div class="flex gap-3 spec-row">
                                <select name="specs[0][key]"
                                    class="spec-key-select w-1/3 px-4 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                                    <option value="">Pilih Key</option>
                                </select>
                                <input type="text" name="specs[0][value]" placeholder="Value"
                                    class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                <button type="button" onclick="this.parentElement.remove()"
                                    class="text-red-500 px-2">×</button>
                            </div>
                        @endforelse
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
                        @foreach($product->suppliers as $index => $sup)
                            <div class="supplier-row bg-slate-50 border border-slate-200 rounded-2xl p-5"
                                data-index="{{ $index }}">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-sm font-semibold text-slate-700">
                                        Supplier #{{ $index + 1 }}
                                    </span>
                                    <button type="button" onclick="this.closest('.supplier-row').remove()"
                                        class="text-red-500 text-xs hover:underline">Hapus</button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-600 mb-1">Supplier</label>
                                        <select name="suppliers[{{ $index }}][supplier_id]"
                                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" required>
                                            @foreach($suppliers as $s)
                                                <option value="{{ $s->id }}"
                                                    {{ $sup->id == $s->id ? 'selected' : '' }}>
                                                    {{ $s->nama_supplier }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-1">Kondisi</label>
                                        <select name="suppliers[{{ $index }}][condition]"
                                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                            <option value="New"
                                                {{ $sup->pivot->condition == 'New' ? 'selected' : '' }}>Baru</option>
                                            <option value="Used"
                                                {{ $sup->pivot->condition == 'Used' ? 'selected' : '' }}>Bekas</option>
                                            <option value="Refurbished"
                                                {{ $sup->pivot->condition == 'Refurbished' ? 'selected' : '' }}>Refurbished</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-1">Stok</label>
                                        <input type="number" name="suppliers[{{ $index }}][stock]"
                                            value="{{ $sup->pivot->stock }}"
                                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-1">Harga Beli</label>
                                        <input type="number" name="suppliers[{{ $index }}][harga_beli]"
                                            value="{{ $sup->pivot->harga_beli }}"
                                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-1">
                                            Harga Jual (Manual)
                                        </label>
                                        <input type="number" name="suppliers[{{ $index }}][harga_jual]"
                                            value="{{ $sup->pivot->harga_jual_manual }}"
                                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- KANAN --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-5">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Simpan Perubahan</h2>
                    <div class="space-y-3">
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
                            Update Produk
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
                            <p>Loading...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
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

    let specIndex = {{ $product->specifications->count() }};
    let supplierIndex = {{ $product->suppliers->count() }};

    function getSelectedCategoryName() {
        const select = document.getElementById('category-select');
        const selected = select.options[select.selectedIndex];
        return selected ? selected.getAttribute('data-name') : '';
    }

    function buildSpecOptions(selectedValue = '') {
        const catName = getSelectedCategoryName();
        const keys = specKeyMap[catName]?.keys || [];

        if (keys.length === 0) {
            return '<option value="">-- Pilih Kategori Dulu --</option>';
        }

        let options = '<option value="">Pilih Key</option>';
        keys.forEach(k => {
            const sel = k === selectedValue ? 'selected' : '';
            options += `<option value="${k}" ${sel}>${k}</option>`;
        });
        return options;
    }

    function updateSpecKeys() {
        const selects = document.querySelectorAll('.spec-key-select');
        selects.forEach(sel => {
            // Ambil nilai yang sudah ada (kalau edit)
            const currentVal = sel.value || sel.getAttribute('data-selected') || '';
            sel.innerHTML = buildSpecOptions(currentVal);
        });
        updateGuide();
    }

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

    function addSupplier() {
        const container = document.getElementById('suppliers-container');
        container.insertAdjacentHTML('beforeend', `
            <div class="supplier-row bg-slate-50 border border-slate-200 rounded-2xl p-5 mt-4">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm font-semibold text-slate-700">Supplier Tambahan</span>
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
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                    </div>
                </div>
            </div>
        `);
        supplierIndex++;
    }

    // Auto-load spec keys saat halaman edit dibuka
    document.addEventListener('DOMContentLoaded', function () {
        updateSpecKeys();
    });
</script>
@endsection
