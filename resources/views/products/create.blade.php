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
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Informasi Produk</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Nama Produk</label>
                            <input type="text" name="name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" placeholder="Contoh: Intel Core i5-12400F" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Kategori</label>
                                <select name="category_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Harga Patokan (Baru)</label>
                                <input type="number" name="selling_price" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" placeholder="1000000" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Deskripsi</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
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

                {{-- SPESIFIKASI (FOR COMPATIBILITY) --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">Spesifikasi Teknis</h2>
                        <button type="button" onclick="addSpec()" class="text-sm font-medium text-blue-600 hover:underline">+ Tambah Spek</button>
                    </div>
                    <div id="specs-container" class="space-y-3">
                        <div class="flex gap-3 spec-row">
                            <input type="text" name="specs[0][key]" placeholder="Contoh: Socket" class="w-1/3 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                            <input type="text" name="specs[0][value]" placeholder="Contoh: LGA1700" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                            <button type="button" onclick="this.parentElement.remove()" class="text-red-500 px-2">×</button>
                        </div>
                    </div>
                </div>

                {{-- SUPPLIER SECTION --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">Supplier & Stok</h2>
                        <button type="button" onclick="addSupplier()" class="px-4 py-2 bg-slate-900 text-white text-xs rounded-xl hover:bg-slate-800">+ Tambah Supplier</button>
                    </div>
                    <div id="suppliers-container" class="space-y-4">
                        <div class="supplier-row bg-slate-50 border border-slate-200 rounded-2xl p-5" data-index="0">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Harga Jual (Opsional)</label>
                                <input type="number" name="suppliers[0][harga_jual]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" placeholder="Kosongkan jika pakai harga patokan">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Supplier</label>
                                    <select name="suppliers[0][supplier_id]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" required>
                                        @foreach($suppliers as $s) <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option> @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Kondisi</label>
                                    <select name="suppliers[0][condition]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                                        <option value="New">Baru</option>
                                        <option value="Used">Bekas</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Stok</label>
                                    <input type="number" name="suppliers[0][stock]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" value="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Harga Beli</label>
                                    <input type="number" name="suppliers[0][harga_beli]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" placeholder="Rp">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KANAN: SUMMARY & SUBMIT --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-5">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Aksi</h2>
                    <p class="text-xs text-slate-500 mb-6">Pastikan semua data spek sudah benar untuk fitur sistem rekomendasi pc otomatis.</p>
                    <div class="space-y-3">
                        <button type="submit" class="w-full py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">Simpan Produk</button>
                        <a href="{{ route('products.index') }}" class="block w-full py-3 rounded-xl bg-slate-100 text-slate-700 text-sm text-center hover:bg-slate-200 transition">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let specIndex = 1;

    function addSpec() {
        const container = document.getElementById('specs-container');
        container.insertAdjacentHTML('beforeend', `
            <div class="flex gap-3 spec-row">
                <input type="text" name="specs[${specIndex}][key]" placeholder="Key" class="w-1/3 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                <input type="text" name="specs[${specIndex}][value]" placeholder="Value" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 px-2">×</button>
            </div>
        `);
        specIndex++;
    }

    let supplierIndex = 1;

    function addSupplier() {
        const container = document.getElementById('suppliers-container');
        container.insertAdjacentHTML('beforeend', `
        <div class="supplier-row bg-slate-50 border border-slate-200 rounded-2xl p-5 mt-4" data-index="${supplierIndex}">
            <div class="flex justify-between items-center mb-4">
                <span class="text-sm font-semibold text-slate-700">Supplier Tambahan</span>
                <button type="button" onclick="this.closest('.supplier-row').remove()" class="text-red-500 text-xs hover:underline">Hapus</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Supplier</label>
                    <select name="suppliers[${supplierIndex}][supplier_id]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" required>
                        @foreach($suppliers as $s) <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Kondisi</label>
                    <select name="suppliers[${supplierIndex}][condition]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm">
                        <option value="New">Baru</option>
                        <option value="Used">Bekas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Stok</label>
                    <input type="number" name="suppliers[${supplierIndex}][stock]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" value="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Harga Beli</label>
                    <input type="number" name="suppliers[${supplierIndex}][harga_beli]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" placeholder="Rp">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Harga Jual (Opsional)</label>
                    <input type="number" name="suppliers[${supplierIndex}][harga_jual]" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm" placeholder="Rp">
                </div>
            </div>
        </div>
    `);
        supplierIndex++;
    }
</script>
@endsection
