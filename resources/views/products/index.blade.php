@extends('layouts.app')

@section('title', 'Manajemen Inventory')

@section('content')
    <div class="px-5">
        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-800">Manajemen Inventory</h1>
                <p class="text-sm text-slate-500">Pantau stok dan kelola data produk dalam satu dasbor.</p>
            </div>

            <div class="flex items-center gap-3">

                <a href="{{ route('products.create') }}"
                    class="inline-flex items-center gap-2 bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-slate-800 transition shadow-sm">
                    <i class="fas fa-plus text-[10px]"></i>
                    Tambah Produk
                </a>
            </div>
        </div>

        {{-- KPI SUMMARY --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Total Produk</p>
                <h3 class="text-2xl font-bold mt-2 text-slate-800">{{ number_format($summary['total_produk']) }}</h3>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Total Stok Unit</p>
                <h3 class="text-2xl font-bold mt-2 text-slate-800">{{ number_format($summary['total_stok']) }}</h3>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Est. Nilai Inventory</p>
                <h3 class="text-2xl font-bold mt-2 text-slate-800">
                    <span class="text-sm font-medium text-slate-400">Rp</span>
                    {{ number_format($summary['nilai_inv'], 0, ',', '.') }}
                </h3>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Stok Menipis</p>
                <div class="flex items-center gap-2 mt-2">
                    {{-- Warna angka otomatis merah kalau ada yang menipis, kalau 0 jadi slate biasa bjir --}}
                    <h3 class="text-2xl font-bold {{ $summary['stok_menipis'] > 0 ? 'text-red-600' : 'text-slate-800' }}">
                        {{ number_format($summary['stok_menipis']) }}
                    </h3>

                    {{-- Badge muncul cuma kalau datanya emang ada dari controller --}}
                    @if ($summary['stok_menipis'] > 0)
                        <span
                            class="text-[10px] px-2 py-0.5 bg-red-50 text-red-600 rounded-full font-medium animate-pulse border border-red-100">
                            Butuh Restock
                        </span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-full font-medium">
                            Semua Aman
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- SEARCH & FILTER --}}
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 mb-6">
            <form action="{{ route('products.index') }}" method="GET" class="flex flex-wrap md:flex-nowrap gap-3">
                {{-- Search Input --}}
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari SKU atau nama produk..."
                        class="w-full pl-11 pr-4 py-2.5 text-sm  rounded-b-xl focus:border-slate-400 outline-none transition">
                </div>

                {{-- Category Dropdown --}}
                <select name="category_id" onchange="this.form.submit()"
                    class="px-4 py-2.5 text-sm border border-slate-200 rounded-xl outline-none focus:border-slate-400 min-w-50 bg-white cursor-pointer">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit"
                    class="px-6 py-2.5 bg-slate-900 text-white font-medium rounded-xl text-sm hover:bg-slate-800 transition">
                    Terapkan Filter
                </button>

                @if (request()->anyFilled(['search', 'category_id']))
                    <a href="{{ route('products.index') }}"
                        class="px-4 py-2.5 bg-slate-100 text-slate-500 rounded-xl text-sm hover:bg-slate-200 transition flex items-center justify-center">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- TABLE --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50/50 text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-left font-medium">Info Produk</th>
                            <th class="px-6 py-4 text-left font-medium">Kategori</th>
                            <th class="px-6 py-4 text-left font-medium">Supplier</th>
                            <th class="px-6 py-4  font-medium text-center">Stok</th>
                            <th class="px-6 py-4 text-left font-medium">Harga Modal</th>
                            <th class="px-6 py-4 text-left font-medium">Harga Jual</th>
                            <th class="px-6 py-4 text-left font-medium">Kondisi</th>
                            <th class="px-6 py-4 font-medium uppercase text-[11px] tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @php
                            $colorMap = [
                                'bg-blue-500',
                                'bg-purple-500',
                                'bg-emerald-500',
                                'bg-amber-500',
                                'bg-pink-500',
                            ];
                        @endphp

                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50/80 transition align-top">
                                {{-- INFO PRODUK --}}
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800 leading-tight">{{ $product->name }}</div>
                                    <div class="text-[10px] text-slate-400 mt-1 font-mono">
                                        #PRD-{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</div>
                                </td>

                                {{-- KATEGORI --}}
                                <td class="px-6 py-4">
                                    @if ($product->category)
                                        <span
                                            class="px-3 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600  uppercase tracking-wider">
                                            {{ $product->category->name }}
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 rounded-full text-[10px] font-semibold bg-slate-50 text-slate-400 border border-dashed border-slate-200 uppercase tracking-wider">
                                            No Category
                                        </span>
                                    @endif
                                </td>

                                {{-- SUPPLIER --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-3">
                                        @foreach ($product->suppliers as $index => $supplier)
                                            @php $color = $colorMap[$index % count($colorMap)]; @endphp
                                            <div class="h-7 flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $color }}"></span>
                                                <span
                                                    class="text-slate-600 text-[11px] font-medium truncate max-w-30">{{ $supplier->nama_supplier }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- STOK --}}
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col gap-3">
                                        @foreach ($product->suppliers as $supplier)
                                            <div class="h-7 flex items-center justify-center">
                                                <span
                                                    class="text-[11px] font-bold px-2.5 py-0.5 rounded-lg {{ $supplier->pivot->stock <= 5 ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-700' }}">
                                                    {{ $supplier->pivot->stock }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- HARGA MODAL --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-3">
                                        @foreach ($product->suppliers as $supplier)
                                            <div class="h-7 flex flex-col justify-center">
                                                <span class="text-[11px] font-medium text-slate-400">Rp
                                                    {{ number_format($supplier->pivot->harga_beli, 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- HARGA JUAL --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-3">
                                        @foreach ($product->suppliers as $supplier)
                                            <div class="h-7 flex flex-col justify-center">
                                                <span class="text-[11px] font-bold text-slate-900">Rp
                                                    {{ number_format($supplier->pivot->harga_jual_manual, 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- KONDISI --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-3">
                                        @foreach ($product->suppliers as $index => $supplier)
                                            @php $color = $colorMap[$index % count($colorMap)]; @endphp
                                            <div class="h-7 flex items-center gap-2">
                                                <span
                                                    class="px-2.5 py-1 rounded-full text-[10px] font-medium  {{ $supplier->pivot->condition == 'New' ? 'bg-emerald-50 text-emerald-600' : 'bg-orange-50 text-orange-600' }}">
                                                    {{ $supplier->pivot->condition }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- STATUS --}}
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col gap-3">
                                        @foreach ($product->suppliers as $supplier)
                                            <div class="h-7 flex items-center justify-center">
                                                @if ($supplier->pivot->stock > 10)
                                                    <span
                                                        class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-600">
                                                        Aman
                                                    </span>
                                                @elseif($supplier->pivot->stock > 0)
                                                    <span
                                                        class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-yellow-100 text-yellow-600">
                                                        Menipis
                                                    </span>
                                                @else
                                                    <span
                                                        class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-rose-100 text-rose-600">
                                                        Kosong
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                {{-- AKSI --}}
                                <td class="text-right px-6 py-4">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('products.edit', $product->id) }}"
                                            class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
                                            title="Edit Produk">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus produk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-24 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="fas fa-box-open text-slate-200 text-2xl"></i>
                                        </div>
                                        <p class="text-slate-400 italic text-sm">Belum ada produk yang terdaftar.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION --}}
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
@endsection
