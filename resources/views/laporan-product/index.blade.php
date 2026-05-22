@extends('layouts.app')

@section('title', 'Laporan Stok')

@section('content')
    <div class="px-5">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Laporan Stok</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Laporan stok berdasarkan data inventory dan pemodal pada database.
                </p>
            </div>

            <a href="{{ route('report.product.download', request()->query()) }}"
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                Export XLSX
            </a>
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
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <select name="pemodal_user_id"
                    class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="">Semua Pemodal</option>
                    @foreach ($pemodals as $pemodal)
                        <option value="{{ $pemodal->id }}" @selected((string) request('pemodal_user_id', request('owner_id')) === (string) $pemodal->id)>
                            {{ $pemodal->name }}{{ $pemodal->role ? ' - ' . $pemodal->role : '' }}
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
                <p class="text-sm text-slate-500">Pemodal Terdata</p>
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

        <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1280px] w-full table-fixed text-[13px]">
                    <colgroup>
                        <col class="w-[52px]">
                        <col class="w-[130px]">
                        <col class="w-[220px]">
                        <col class="w-[86px]">
                        <col class="w-[118px]">
                        <col class="w-[118px]">
                        <col class="w-[82px]">
                        <col class="w-[78px]">
                        <col class="w-[90px]">
                        <col class="w-[128px]">
                        <col class="w-[140px]">
                        <col class="w-[124px]">
                        <col class="w-[70px]">
                    </colgroup>
                    <thead class="bg-slate-50/90 text-slate-500">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium">No</th>
                            <th class="px-3 py-3 text-left font-medium">Pemodal</th>
                            <th class="px-3 py-3 text-left font-medium">Nama Barang</th>
                            <th class="px-3 py-3 text-left font-medium">Kondisi</th>
                            <th class="px-3 py-3 text-right font-medium">Modal</th>
                            <th class="px-3 py-3 text-right font-medium">Harga Jual</th>
                            <th class="px-3 py-3 text-right font-medium">Stok Awal</th>
                            <th class="px-3 py-3 text-right font-medium">Terjual</th>
                            <th class="px-3 py-3 text-right font-medium">Stok Ready</th>
                            <th class="px-3 py-3 text-right font-medium">Total Modal</th>
                            <th class="px-3 py-3 text-left font-medium">Supplier</th>
                            <th class="px-3 py-3 text-left font-medium">Seller</th>
                            <th class="px-3 py-3 text-center font-medium">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reportRows as $index => $row)
                            @php
                                $sellerBreakdown = collect($row->seller_breakdown ?? [])
                                    ->map(
                                        fn($seller) => [
                                            'name' => trim((string) data_get($seller, 'name')),
                                            'qty' => (int) data_get($seller, 'qty', 0),
                                        ],
                                    )
                                    ->filter(fn($seller) => $seller['name'] !== '')
                                    ->values();
                                $firstSeller = $sellerBreakdown->first();
                                $remainingSellerCount = max($sellerBreakdown->count() - 1, 0);
                                $detailModalId = 'product-stock-detail-' . $row->product_supplier_id;
                            @endphp
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-3 py-3.5 align-middle text-slate-500">
                                    {{ ($reportRows->firstItem() ?? 1) + $index }}
                                </td>
                                <td class="px-3 py-3.5 align-middle text-slate-700">
                                    @if ($row->pemodal_name)
                                        <div class="truncate font-semibold leading-5 text-slate-900">
                                            {{ $row->pemodal_name }}</div>
                                        @if ($row->pemodal_role)
                                            <div class="mt-0.5 truncate text-[11px] capitalize text-slate-400">
                                                {{ $row->pemodal_role }}</div>
                                        @endif
                                    @else
                                        <span class="font-medium text-rose-600">Belum diisi</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5 align-middle font-medium text-slate-900">
                                    <div class="truncate leading-5">{{ $row->product_name ?: '-' }}</div>
                                    <div class="mt-0.5 truncate text-[11px] text-slate-500">
                                        {{ $row->category_name ?: 'Tanpa kategori' }}
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 align-middle text-slate-700">
                                    <span
                                        class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                        {{ $row->condition ?: '-' }}
                                    </span>
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-right align-middle font-semibold tabular-nums text-slate-900">
                                    Rp {{ number_format((float) $row->modal, 0, ',', '.') }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-right align-middle font-semibold tabular-nums text-slate-900">
                                    Rp {{ number_format((float) $row->harga_jual, 0, ',', '.') }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-right align-middle font-semibold tabular-nums text-slate-900">
                                    {{ number_format((int) $row->stock_awal) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-right align-middle font-semibold tabular-nums text-amber-600">
                                    {{ number_format((int) $row->sold_qty) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-right align-middle font-semibold tabular-nums text-emerald-600">
                                    {{ number_format((int) $row->stock_ready) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-right align-middle font-semibold tabular-nums text-slate-900">
                                    Rp {{ number_format((float) $row->total_modal, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3.5 align-middle text-slate-700">
                                    <div class="line-clamp-2 leading-5">{{ $row->supplier_name ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-3.5 align-middle text-slate-700">
                                    @if ($firstSeller)
                                        <div class="flex min-w-0 items-center gap-1.5">
                                            <span
                                                class="truncate font-medium text-slate-800">{{ $firstSeller['name'] }}</span>
                                            @if ($remainingSellerCount > 0)
                                                <span
                                                    class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                                    +{{ $remainingSellerCount }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5 text-center align-middle">
                                    <button type="button" onclick="openModal('{{ $detailModalId }}')"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                        title="Detail laporan">
                                        <i class="fas fa-eye text-[13px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-4 py-10 text-center text-sm text-slate-500">
                                    Belum ada data stok inventory untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach ($reportRows as $row)
            @php
                $sellerBreakdown = collect($row->seller_breakdown ?? [])
                    ->map(
                        fn($seller) => [
                            'name' => trim((string) data_get($seller, 'name')),
                            'qty' => (int) data_get($seller, 'qty', 0),
                        ],
                    )
                    ->filter(fn($seller) => $seller['name'] !== '')
                    ->values();
                $detailModalId = 'product-stock-detail-' . $row->product_supplier_id;
            @endphp
            <x-modal id="{{ $detailModalId }}" title="Detail Stok" size="lg">
                <div class="space-y-5">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-400">Barang</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $row->product_name ?: '-' }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $row->category_name ?: 'Tanpa kategori' }} -
                            {{ $row->condition ?: '-' }}</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Pemodal</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $row->pemodal_name ?: 'Belum diisi' }}</p>
                            @if ($row->pemodal_role)
                                <p class="mt-0.5 text-xs capitalize text-slate-400">{{ $row->pemodal_role }}</p>
                            @endif
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Supplier</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $row->supplier_name ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Tanggal Masuk</p>
                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $row->entry_date ? \Carbon\Carbon::parse($row->entry_date)->format('d/m/Y') : '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Modal</th>
                                    <th class="px-4 py-3 text-left font-medium">Harga Jual</th>
                                    <th class="px-4 py-3 text-right font-medium">Stok Awal</th>
                                    <th class="px-4 py-3 text-right font-medium">Terjual</th>
                                    <th class="px-4 py-3 text-right font-medium">Stok Ready</th>
                                    <th class="px-4 py-3 text-right font-medium">Total Modal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums">Rp
                                        {{ number_format((float) $row->modal, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums">Rp
                                        {{ number_format((float) $row->harga_jual, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">
                                        {{ number_format((int) $row->stock_awal) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-amber-600">
                                        {{ number_format((int) $row->sold_qty) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-600">
                                        {{ number_format((int) $row->stock_ready) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">Rp
                                        {{ number_format((float) $row->total_modal, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Seller</h4>
                        @if ($sellerBreakdown->isNotEmpty())
                            <div class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium">Nama Seller</th>
                                            <th class="px-4 py-3 text-right font-medium">Qty Terjual</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($sellerBreakdown as $seller)
                                            <tr>
                                                <td class="px-4 py-3 font-medium text-slate-800">{{ $seller['name'] }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">
                                                    {{ number_format($seller['qty']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mt-2 rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-500">
                                Belum ada seller dari transaksi untuk stok ini.
                            </p>
                        @endif
                    </div>
                </div>
            </x-modal>
        @endforeach

        <div class="mt-6">
            {{ $reportRows->links() }}
        </div>
    </div>
@endsection
