@extends('layouts.app')

@section('title', 'Laporan Transaksi')

@section('content')
<div class="px-5">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Laporan Transaksi</h1>
            <p class="mt-1 text-sm text-slate-500">
                Laporan Transaksi Penjualan dan Service.
            </p>
        </div>

        <a
            href="{{ route('report.download', request()->query()) }}"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm text-white transition hover:bg-slate-800">
            <i class="fas fa-download text-xs"></i>
            Download CSV
        </a>
    </div>

    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm">
        <form action="{{ route('report') }}" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-6">
            <div class="md:col-span-2">
                <label for="search" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">
                    Pencarian
                </label>
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari seller, customer, atau barang..."
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div>
                <label for="date_from" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">
                    Tgl Mulai
                </label>
                <input
                    id="date_from"
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div>
                <label for="date_to" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">
                    Tgl Selesai
                </label>
                <input
                    id="date_to"
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div class="flex items-end">
                <button
                    type="submit"
                    class="inline-flex h-[42px] w-full items-center justify-center rounded-xl bg-slate-900 px-4 text-sm text-white transition hover:bg-slate-800">
                    Terapkan Filter
                </button>
            </div>

            <div class="flex items-end">
                <a
                    href="{{ route('report') }}"
                    class="inline-flex h-[42px] w-full items-center justify-center rounded-xl border border-slate-200 px-4 text-sm text-slate-600 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Jumlah transaksi</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_rows']) }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Harga Jual</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['total_selling'], 0, ',', '.') }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Service</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['total_service'], 0, ',', '.') }}</h3>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Profit</p>
            <h3 class="mt-1 text-2xl font-semibold text-emerald-600">Rp {{ number_format($summary['total_profit'], 0, ',', '.') }}</h3>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1900px] w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-4 text-left font-medium">Seller</th>
                        <th class="px-4 py-4 text-left font-medium">Date</th>
                        <th class="px-4 py-4 text-left font-medium">Tipe</th>
                        <th class="px-4 py-4 text-left font-medium">Nama Barang</th>
                        <th class="px-4 py-4 text-left font-medium">Spesifikasi</th>
                        <th class="px-4 py-4 text-right font-medium">Qty</th>
                        <th class="px-4 py-4 text-left font-medium">Customer</th>
                        <th class="px-4 py-4 text-left font-medium">Alamat</th>
                        <th class="px-4 py-4 text-right font-medium">Total Modal</th>
                        <th class="px-4 py-4 text-right font-medium">Total Jual</th>
                        <th class="px-4 py-4 text-right font-medium">Biaya Tambahan</th>
                        <th class="px-4 py-4 text-right font-medium">Profit Kotor</th>
                        <th class="px-4 py-4 text-right font-medium">Penjual</th>
                        <th class="px-4 py-4 text-right font-medium">NATOPC</th>
                        <th class="px-4 py-4 text-left font-medium">Status</th>
                        <th class="px-4 py-4 text-left font-medium">Desc</th>
                        <th class="px-4 py-4 text-left font-medium">Garansi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($reportRows as $row)
                    @php
                    $isRakit = $row->transaction_mode === 'rakit_pc';
                    $productParts = $isRakit
                    ? collect()
                    : collect(explode(',', (string) ($row->product_name ?? '')))
                    ->map(fn($item) => trim($item))
                    ->filter()
                    ->values();
                    $specParts = collect(explode($isRakit ? ',' : '|', (string) ($row->item_specification ?? '')))
                    ->map(fn($item) => trim($item))
                    ->filter()
                    ->values();
                    @endphp
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-4 align-top text-slate-700">
                            <span class="font-medium text-slate-900">{{ $row->seller_name ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-4 align-top text-slate-600">
                            {{ $row->transaction_date ? date('d, M, Y', strtotime((string) $row->transaction_date)) : '-' }}
                        </td>
                        <td class="px-4 py-4 align-top text-slate-700">
                            {{ $row->transaction_mode === 'rakit_pc' ? 'Rakit PC' : 'Sparepart only' }}
                        </td>
                        <td class="px-4 py-4 align-top text-slate-900">
                            @if ($isRakit)
                            <div class="font-semibold">{{ $row->product_name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">Build PC</div>
                            @elseif ($productParts->isNotEmpty())
                            <ul class="space-y-1">
                                @foreach ($productParts as $itemName)
                                <li class="text-slate-800">{{ $loop->iteration }}. {{ $itemName }}</li>
                                @endforeach
                            </ul>
                            @else
                            <span>-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top text-slate-600">
                            @if ($specParts->isNotEmpty())
                            <ul class="space-y-1">
                                @foreach ($specParts as $spec)
                                <li class="text-slate-700">{{ $spec }}</li>
                                @endforeach
                            </ul>
                            @else
                            <span>-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right align-top font-semibold tabular-nums text-slate-700">
                            {{ number_format((int) $row->quantity) }}
                        </td>
                        <td class="px-4 py-4 align-top text-slate-700">
                            <div class="font-medium text-slate-900">{{ $row->customer_name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $row->customer_phone ?: '-' }}
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-slate-700">
                            {{ $row->customer_address ?: '-' }}
                        </td>
                        <td class="px-4 py-4 text-right align-top tabular-nums text-slate-900">
                            <div class="font-semibold">
                                Rp {{ number_format((float) $row->modal_total, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right align-top tabular-nums text-slate-900">
                            <div class="font-semibold">
                                Rp {{ number_format((float) $row->selling_total, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right align-top font-semibold tabular-nums text-sky-600">
                            Rp {{ number_format((float) $row->service_total, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 text-right align-top font-semibold tabular-nums {{ (float) $row->gross_profit_total >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            Rp {{ number_format((float) $row->gross_profit_total, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 text-right align-top font-semibold tabular-nums text-emerald-700">
                            Rp {{ number_format((float) $row->seller_profit_share, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 text-right align-top font-semibold tabular-nums text-indigo-700">
                            Rp {{ number_format((float) $row->natopc_profit_share, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 align-top">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $row->status === 'Completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $row->status ?: '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <div class="flex items-start gap-2">
                                <span class="text-slate-700 whitespace-pre-wrap max-w-[150px]">{{ $row->transaction_desc ?: '-' }}</span>
                                <button type="button" onclick="openDescModal({{ $row->transaction_id }}, '{{ htmlspecialchars((string) $row->transaction_desc, ENT_QUOTES) }}')" class="p-1 text-slate-400 hover:text-slate-700">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <div class="flex items-start gap-2">
                                <span class="text-slate-700 max-w-[150px]">{!! $row->warranty_details_list === 'Kosong' ? '-' : $row->warranty_details_list !!}</span>
                                <button type="button" onclick="openWarrantyModal({{ $row->transaction_id }}, '{{ htmlspecialchars(str_replace('<br>', ' ', (string) $row->warranty_details_list), ENT_QUOTES) }}')" class="p-1 text-slate-400 hover:text-slate-700">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="17" class="px-4 py-10 text-center text-sm text-slate-500">
                            Belum ada data transaksi yang sesuai filter.
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

<x-modal id="descModal" title="Edit Catatan Transaksi">
    <form id="descForm" method="POST" action="">
        @csrf
        <div class="mb-4">
            <label class="mb-2 block text-sm font-medium text-slate-700">Catatan (Desc)</label>
            <textarea name="description" id="descInput" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-slate-400 focus:outline-none"></textarea>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeModal('descModal')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Simpan</button>
        </div>
    </form>
</x-modal>

<x-modal id="warrantyModal" title="Edit Garansi (Semua Item di Transaksi Ini)">
    <form id="warrantyForm" method="POST" action="">
        @csrf
        <div class="mb-4">
            <label class="mb-2 block text-sm font-medium text-slate-700">Tahun Garansi</label>
            <input type="text" name="warranty" id="warrantyInput" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-slate-400 focus:outline-none" placeholder="Misal: 1 Tahun">
            <p class="mt-2 text-xs text-amber-600">Peringatan: Menyimpan form ini akan memperbarui status masa garansi bagi SEMUA item stock supplier yang disertakan dalam transaksi ini.</p>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeModal('warrantyModal')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Simpan</button>
        </div>
    </form>
</x-modal>

<script>
    function openDescModal(transactionId, desc) {
        document.getElementById('descForm').action = `/transactions/${transactionId}/desc`;
        document.getElementById('descInput').value = desc;
        document.getElementById('descModal').classList.remove('hidden');
        document.getElementById('descModal').classList.add('flex');
    }
    
    function openWarrantyModal(transactionId, warranty) {
        document.getElementById('warrantyForm').action = `/transactions/${transactionId}/warranty`;
        document.getElementById('warrantyInput').value = warranty === 'Kosong' ? '' : warranty;
        document.getElementById('warrantyModal').classList.remove('hidden');
        document.getElementById('warrantyModal').classList.add('flex');
    }
</script>
@endsection