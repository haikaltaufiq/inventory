@extends('layouts.app')

@section('title', 'Laporan Stok')

@section('content')
<div class="px-5">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Laporan Stok Berdasarkan Transaksi</h1>
            <p class="mt-1 text-sm text-slate-500">
                Hanya menampilkan barang yang sudah pernah masuk ke transaksi.
            </p>
        </div>
    </div>

    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm">
        <form action="{{ route('report.product') }}" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari produk, brand, supplier, pemodal, penjual, customer..."
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">

            <select
                name="category_id"
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <button
                type="submit"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm text-white transition hover:bg-slate-800">
                Terapkan Filter
            </button>

            <a
                href="{{ route('report.product') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Baris Transaksi</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_rows']) }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Qty</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_qty']) }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Produk Terjual</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_products']) }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Customer Unik</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_customers']) }}</h3>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-4 text-left font-medium">No</th>
                        <th class="px-4 py-4 text-left font-medium">Nama Produk</th>
                        <th class="px-4 py-4 text-left font-medium">Brand</th>
                        <th class="px-4 py-4 text-left font-medium">Category</th>
                        <th class="px-4 py-4 text-left font-medium">Supplier &amp; Kondisi</th>
                        <th class="px-4 py-4 text-left font-medium">Pemodal</th>
                        <th class="px-4 py-4 text-left font-medium">Penjual</th>
                        <th class="px-4 py-4 text-left font-medium">Nama Customer</th>
                        <th class="px-4 py-4 text-right font-medium">Qty</th>
                        <th class="px-4 py-4 text-left font-medium">Tanggal</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($reportRows as $index => $row)
                        @php
                            $condition = $row->supplier_condition ?: '-';
                            $conditionClasses = match ($row->supplier_condition) {
                                'Used' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                'Refurbished' => 'bg-violet-50 text-violet-700 ring-violet-200',
                                'New' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                default => 'bg-slate-100 text-slate-600 ring-slate-200',
                            };
                            $conditionDotClass = match ($row->supplier_condition) {
                                'Used' => 'bg-amber-500',
                                'Refurbished' => 'bg-violet-500',
                                'New' => 'bg-sky-500',
                                default => 'bg-slate-400',
                            };
                        @endphp

                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-4 text-slate-500">
                                {{ ($reportRows->firstItem() ?? 1) + $index }}
                            </td>
                            <td class="px-4 py-4 font-medium text-slate-900">
                                {{ $row->product_name ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ $row->brand ?: '-' }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">
                                    {{ $row->category_name ?: '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-slate-800">{{ $row->supplier_name ?: '-' }}</span>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 {{ $conditionClasses }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $conditionDotClass }}"></span>
                                        <span>{{ $condition }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ $row->pemodal_name ?: '-' }}
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ $row->sales_name ?: '-' }}
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ $row->customer_name ?: '-' }}
                            </td>
                            <td class="px-4 py-4 text-right font-semibold tabular-nums text-slate-900">
                                {{ number_format($row->quantity) }}
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ $row->transaction_created_at ? \Illuminate\Support\Carbon::parse($row->transaction_created_at)->format('d-m-Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada produk yang memiliki data transaksi.
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
