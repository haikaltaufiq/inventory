@extends('layouts.app')

@section('title', 'Laporan Stok')

@section('content')
<div class="px-5">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Laporan Stok</h1>
            <p class="mt-1 text-sm text-slate-500">
                Laporan Stok Masing-masing Pemodal.
            </p>
        </div>
    </div>

    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm">
        <form action="{{ route('report.product') }}" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Cari pemodal, barang, supplier, penjual..."
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">

            <select name="category_id"
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id')===(string) $category->id)>
                    {{ $category->name }}
                </option>
                @endforeach
            </select>

            <select name="owner_id"
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
                <option value="">Semua Pemodal</option>
                @foreach ($owners as $owner)
                <option value="{{ $owner->id }}" @selected((string) request('owner_id')===(string) $owner->id)>
                    {{ $owner->name }}
                </option>
                @endforeach
            </select>

            <button type="submit"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm text-white transition hover:bg-slate-800">
                Terapkan Filter
            </button>

            <a href="{{ route('report.product') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Pemodal Owner</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_pemodal']) }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Stock Awal</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_stock_awal']) }}
            </h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Terjual</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_terjual']) }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Stock Ready</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_stock_ready']) }}
            </h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Modal</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">Rp
                {{ number_format($summary['total_modal'], 0, ',', '.') }}
            </h3>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-4 text-left font-medium">No</th>
                        <th class="px-4 py-4 text-left font-medium">Pemodal</th>
                        <th class="px-4 py-4 text-left font-medium">Nama Barang</th>
                        <th class="px-4 py-4 text-right font-medium">Modal</th>
                        <th class="px-4 py-4 text-right font-medium">Stock Awal</th>
                        <th class="px-4 py-4 text-right font-medium">Terjual</th>
                        <th class="px-4 py-4 text-right font-medium">Stock Ready</th>
                        <th class="px-4 py-4 text-right font-medium">Total Modal</th>
                        <th class="px-4 py-4 text-left font-medium">Supplier</th>
                        <th class="w-[280px] px-4 py-4 text-left font-medium">Seller</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($reportRows as $index => $row)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-4 text-slate-500">
                            {{ ($reportRows->firstItem() ?? 1) + $index }}
                        </td>
                        <td class="px-4 py-4 text-slate-700">
                            {{ $row->pemodal_name ?: '-' }}
                        </td>
                        <td class="px-4 py-4 font-medium text-slate-900">
                            <div>{{ $row->product_name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->category_name ?: 'Tanpa kategori' }}
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right font-semibold tabular-nums text-slate-900">
                            Rp {{ number_format((float) $row->modal, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 text-right font-semibold tabular-nums text-slate-900">
                            {{ number_format((int) $row->stock_awal) }}
                        </td>
                        <td class="px-4 py-4 text-right font-semibold tabular-nums text-amber-600">
                            {{ number_format((int) $row->sold_qty) }}
                        </td>
                        <td class="px-4 py-4 text-right font-semibold tabular-nums text-emerald-600">
                            {{ number_format((int) $row->stock_ready) }}
                        </td>
                        <td class="px-4 py-4 text-right font-semibold tabular-nums text-slate-900">
                            Rp {{ number_format((float) $row->total_modal, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 align-top text-slate-700">
                            {{ $row->supplier_name ?: '-' }}
                        </td>
                        <td class="w-[280px] px-4 py-4 align-top text-slate-700">
                            @php
                            $sellerBreakdown = collect($row->seller_breakdown ?? []);
                            @endphp

                            @if ($sellerBreakdown->isNotEmpty())
                            <div class="flex max-w-[280px] flex-wrap gap-1.5">
                                @foreach ($sellerBreakdown as $seller)
                                <span
                                    class="inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[12px] leading-4 text-slate-700">
                                    <span class="truncate">{{ $seller['name'] }}</span>
                                    <span
                                        class="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-600 ring-1 ring-slate-200">
                                        {{ number_format((int) $seller['qty']) }}
                                    </span>
                                </span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500">
                            Belum ada data stok untuk pemodal dengan peran owner.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $reportRows->links() }}
    </div>
</div>
@endsection