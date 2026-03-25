@extends('layouts.app')

@section('title', 'Transaksi')

@section('content')
<div class="px-5">

    {{-- HEADER CARD --}}
    <div class="mb-6">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">

            {{-- TOP ROW --}}
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                    Transaksi Komponen
                </h1>

                <div class="flex gap-3">
                    {{-- SEARCH --}}
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            placeholder="Cari produk..."
                            class="w-72 pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl
                               text-sm font-medium
                               focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>

                    {{-- SORT --}}
                    <select
                        class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl
                           text-sm font-medium text-slate-600
                           focus:ring-2 focus:ring-slate-900/10">
                        <option>Terbaru</option>
                        <option>Harga Termurah</option>
                        <option>Harga Termahal</option>
                    </select>
                </div>
            </div>

            {{-- FILTER KATEGORI --}}
            <div class="flex gap-2 overflow-x-auto no-scrollbar">
                @php
                $categories = ['Semua', 'Processor', 'VGA Card', 'Motherboard', 'RAM', 'SSD', 'PSU'];
                @endphp

                @foreach($categories as $cat)
                <button
                    class="px-4 py-2 rounded-full text-sm font-medium
                    {{ $cat === 'Semua'
                        ? 'bg-slate-900 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $cat }}
                </button>
                @endforeach
            </div>

        </div>
    </div>



    <div class="flex gap-6 mb-8">

        {{-- PRODUCT GRID --}}
        <div class="flex-1">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <x-product-card
                    name="Intel Core i7 14700K"
                    category="Processor"
                    supplier="Intel Official"
                    price="6899000"
                    image="https://images.unsplash.com/photo-1587202372775-e229f172b9d7?w=500" />

                <x-product-card
                    name="AMD Ryzen 7 7800X3D"
                    category="Processor"
                    supplier="AMD Store"
                    price="7399000"
                    image="https://images.unsplash.com/photo-1612198188060-c7c2a3b66eae?w=500" />

                <x-product-card
                    name="NVIDIA RTX 4070 Super"
                    category="VGA Card"
                    supplier="NVIDIA Partner"
                    price="12999000"
                    image="https://images.unsplash.com/photo-1591489378430-ef2f4c626b35?w=500" />

                <x-product-card
                    name="Corsair Vengeance 32GB DDR5"
                    category="RAM"
                    supplier="Corsair"
                    price="2499000"
                    image="https://images.unsplash.com/photo-1624705002806-5d72df19c3ad?w=500" />

                <x-product-card
                    name="Samsung 990 Pro 1TB NVMe"
                    category="SSD"
                    supplier="Samsung"
                    price="2199000"
                    image="https://images.unsplash.com/photo-1587202372583-49330c88dfd2?w=500" />

                <x-product-card
                    name="ASUS ROG STRIX Z790-E"
                    category="Motherboard"
                    supplier="ASUS"
                    price="7599000"
                    image="https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04?w=500" />

            </div>
        </div>

        {{-- SIDEBAR ORDER --}}
        <div class="w-96 bg-white rounded-2xl shadow-sm p-6 flex flex-col">

            <h2 class="text-lg font-semibold mb-6">
                Ringkasan Transaksi
            </h2>

            {{-- ORDER LIST --}}
            <div class="flex-1 space-y-4 overflow-y-auto">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-100"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-800">
                            Intel Core i7 14700K
                        </p>
                        <p class="text-xs text-slate-500">
                            Rp 6.899.000
                        </p>
                    </div>
                    <span class="text-sm font-medium">1x</span>
                </div>
            </div>

            {{-- TOTAL --}}
            <div class="pt-6 mt-6 border-t">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-500">Subtotal</span>
                    <span class="font-medium">Rp 6.899.000</span>
                </div>
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total</span>
                    <span>Rp 6.899.000</span>
                </div>

                <button
                    class="w-full mt-6 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800 transition">
                    Proses Transaksi
                </button>
            </div>

        </div>
    </div>
</div>
@endsection