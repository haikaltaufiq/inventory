@extends('layouts.app')

@section('title', 'Simulasi Rakit PC')

@section('content')
<div class="px-5">

    {{-- HEADER --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                Simulasi Rakit PC
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Estimasi biaya & kompatibilitas komponen PC
            </p>
        </div>

        <button
            class="px-4 py-2 rounded-xl bg-slate-100 text-sm font-medium text-slate-600 hover:bg-slate-200 transition">
            Reset Build
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        {{-- LEFT: COMPONENT SELECTOR --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- COMPONENT CARD --}}
            @php
            $components = [
            ['label' => 'Processor', 'value' => 'Intel Core i7 14700K', 'price' => 'Rp 6.899.000'],
            ['label' => 'Motherboard', 'value' => 'ASUS ROG STRIX Z790-E', 'price' => 'Rp 7.599.000'],
            ['label' => 'RAM', 'value' => 'Corsair Vengeance 32GB DDR5', 'price' => 'Rp 2.499.000'],
            ['label' => 'VGA', 'value' => 'NVIDIA RTX 4070 Super', 'price' => 'Rp 12.999.000'],
            ['label' => 'Storage', 'value' => 'Samsung 990 Pro 1TB NVMe', 'price' => 'Rp 2.199.000'],
            ['label' => 'Power Supply', 'value' => 'Corsair RM850x 850W', 'price' => 'Rp 2.350.000'],
            ];
            @endphp

            @foreach ($components as $item)
            <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">
                        {{ $item['label'] }}
                    </p>
                    <p class="font-medium text-slate-800">
                        {{ $item['value'] }}
                    </p>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $item['price'] }}
                    </p>
                </div>

                <button
                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800 transition">
                    Ganti
                </button>
            </div>
            @endforeach

            {{-- COMPATIBILITY INFO --}}
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-5">
                <p class="text-sm font-medium text-emerald-700">
                    ✓ Semua komponen kompatibel
                </p>
                <p class="text-xs text-emerald-600 mt-1">
                    Socket, RAM type, dan daya PSU mencukupi
                </p>
            </div>
        </div>

        {{-- RIGHT: BUILD SUMMARY --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col">

            <h2 class="text-lg font-semibold text-slate-800 mb-4">
                Ringkasan Build
            </h2>

            {{-- SUMMARY LIST --}}
            <div class="space-y-3 flex-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Processor</span>
                    <span>Rp 6.899.000</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Motherboard</span>
                    <span>Rp 7.599.000</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">RAM</span>
                    <span>Rp 2.499.000</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">VGA</span>
                    <span>Rp 12.999.000</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Storage</span>
                    <span>Rp 2.199.000</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">PSU</span>
                    <span>Rp 2.350.000</span>
                </div>
            </div>

            {{-- POWER ESTIMATION --}}
            <div class="mt-6 bg-slate-50 rounded-xl p-4 text-sm">
                <div class="flex justify-between mb-1">
                    <span class="text-slate-500">Estimasi Daya</span>
                    <span class="font-medium">620W</span>
                </div>
                <p class="text-xs text-slate-400">
                    PSU 850W direkomendasikan
                </p>
            </div>

            {{-- TOTAL --}}
            <div class="mt-6 pt-4 border-t">
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total Estimasi</span>
                    <span>Rp 35.545.000</span>
                </div>

                <button
                    class="w-full mt-5 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800 transition">
                    Simpan Build
                </button>
            </div>
        </div>

    </div>
</div>
@endsection