@extends('layouts.app')

@section('title', 'Laporan Stok')

@section('content')
<div class="px-5">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">
            Laporan Stok Produk
        </h1>

        <div class="flex gap-2">
            <button
                class="inline-flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-xl text-sm hover:bg-slate-50 transition">
                Export PDF
            </button>

            <button
                class="inline-flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-slate-800 transition">
                Download CSV
            </button>
        </div>
    </div>

    {{-- FILTER & SEARCH (DUMMY UI) --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-6">
        <form class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input
                type="text"
                placeholder="Cari produk / SKU"
                class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-200">

            <select class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl">
                <option>Kategori</option>
                <option>Processor</option>
                <option>VGA</option>
                <option>RAM</option>
                <option>Storage</option>
            </select>

            <select class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl">
                <option>Status Stok</option>
                <option>Aman</option>
                <option>Menipis</option>
                <option>Habis</option>
            </select>

            <input
                type="number"
                placeholder="Stok <="
                class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl">

            <button
                type="button"
                class="bg-slate-900 text-white rounded-xl text-sm hover:bg-slate-800 transition">
                Terapkan Filter
            </button>
        </form>
    </div>

    {{-- KPI SUMMARY --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Produk</p>
            <h3 class="text-2xl font-semibold mt-1">128</h3>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Stok Unit</p>
            <h3 class="text-2xl font-semibold mt-1">2.430</h3>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Nilai Inventory</p>
            <h3 class="text-2xl font-semibold mt-1">
                Rp 1.245.000.000
            </h3>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Produk Stok Menipis</p>
            <h3 class="text-2xl font-semibold mt-1 text-red-600">
                7
            </h3>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-6 py-4 text-left font-medium">No</th>
                    <th class="px-6 py-4 text-left font-medium">Produk</th>
                    <th class="px-6 py-4 text-left font-medium">SKU</th>
                    <th class="px-6 py-4 text-left font-medium">Kategori</th>
                    <th class="px-6 py-4 text-left font-medium">Stok</th>
                    <th class="px-6 py-4 text-left font-medium">Harga</th>
                    <th class="px-6 py-4 text-left font-medium">Nilai</th>
                    <th class="px-6 py-4 text-left font-medium">Status</th>
                </tr>
            </thead>

            <tbody>
                {{-- ROW DUMMY --}}
                <tr class="hover:bg-slate-50 transition border-b border-gray-200">
                    <td class="px-6 py-4">1</td>
                    <td class="px-6 py-4 font-medium">
                        Intel Core i5 13400F
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        CPU-INT-13400F
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs bg-slate-100 text-slate-600">
                            Processor
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium">
                        4
                    </td>
                    <td class="px-6 py-4">
                        Rp 3.200.000
                    </td>
                    <td class="px-6 py-4 font-medium">
                        Rp 12.800.000
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-600">
                            Menipis
                        </span>
                    </td>
                </tr>

                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4">2</td>
                    <td class="px-6 py-4 font-medium">
                        RTX 4060 Ti
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        VGA-NV-4060TI
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs bg-slate-100 text-slate-600">
                            VGA
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium">
                        18
                    </td>
                    <td class="px-6 py-4">
                        Rp 6.800.000
                    </td>
                    <td class="px-6 py-4 font-medium">
                        Rp 122.400.000
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600">
                            Aman
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection