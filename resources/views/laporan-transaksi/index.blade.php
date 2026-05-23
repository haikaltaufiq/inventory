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

            <a href="{{ route('report.download', request()->query()) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm text-white transition hover:bg-slate-800">
                <i class="fas fa-download text-xs"></i>
                Export File
            </a>
        </div>

        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm">
            <form action="{{ route('report') }}" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label for="search" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">
                        Pencarian
                    </label>
                    <input id="search" type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari seller, customer, atau barang..."
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>

                <div>
                    <label for="date_from"
                        class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">
                        Tgl Mulai
                    </label>
                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>

                <div>
                    <label for="date_to" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">
                        Tgl Selesai
                    </label>
                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                        class="inline-flex h-[42px] w-full items-center justify-center rounded-xl bg-slate-900 px-4 text-sm text-white transition hover:bg-slate-800">
                        Terapkan Filter
                    </button>
                </div>

                <div class="flex items-end">
                    <a href="{{ route('report') }}"
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
                <h3 class="mt-1 text-2xl font-semibold text-slate-900">Rp
                    {{ number_format($summary['total_selling'], 0, ',', '.') }}</h3>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Service</p>
                <h3 class="mt-1 text-2xl font-semibold text-slate-900">Rp
                    {{ number_format($summary['total_service'], 0, ',', '.') }}</h3>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Profit</p>
                <h3 class="mt-1 text-2xl font-semibold text-emerald-600">Rp
                    {{ number_format($summary['total_profit'], 0, ',', '.') }}</h3>
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
                            <th class="px-4 py-4 text-center font-medium">Aksi</th>
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
                                $specParts = collect(
                                    explode($isRakit ? ',' : '|', (string) ($row->item_specification ?? '')),
                                )
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
                                        <div class="font-semibold line-clamp-1 max-w-[150px]">
                                            {{ $row->product_name ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">Build PC</div>
                                    @elseif ($productParts->isNotEmpty())
                                        <div class="font-semibold line-clamp-1 max-w-[150px]">{{ $productParts->first() }}
                                        </div>
                                        @if ($productParts->count() > 1)
                                            <div class="mt-1 text-xs text-slate-500 whitespace-nowrap">+
                                                {{ $productParts->count() - 1 }} part lainnya</div>
                                        @endif
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    <div class="line-clamp-2 max-w-[150px] text-xs">
                                        {{ $specParts->isNotEmpty() ? implode(', ', $specParts->toArray()) : '-' }}
                                    </div>
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
                                    <div class="line-clamp-2 max-w-[120px] text-xs">
                                        {{ $row->customer_address ?: '-' }}
                                    </div>
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
                                <td
                                    class="px-4 py-4 text-right align-top font-semibold tabular-nums {{ (float) $row->gross_profit_total >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    Rp {{ number_format((float) $row->gross_profit_total, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-right align-top font-semibold tabular-nums text-emerald-700">
                                    Rp {{ number_format((float) $row->seller_profit_share, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-right align-top font-semibold tabular-nums text-indigo-700">
                                    Rp {{ number_format((float) $row->natopc_profit_share, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $row->status === 'Completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $row->status ?: '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-start gap-2">
                                        <span
                                            class="text-slate-700 text-xs line-clamp-2 max-w-[150px]">{{ $row->transaction_desc ?: '-' }}</span>
                                        <button type="button"
                                            onclick="openDescModal({{ $row->transaction_id }}, '{{ htmlspecialchars((string) $row->transaction_desc, ENT_QUOTES) }}')"
                                            class="p-1 text-slate-400 hover:text-slate-700 shrink-0">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                                
                                <td class="px-4 py-4 align-top text-center w-20">
                                    <button type="button" onclick="openDetailModal({{ $row->transaction_id }})"
                                        class="flex items-center justify-center h-8 w-max rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 hover:bg-indigo-100 hover:border-indigo-200 transition-all whitespace-nowrap gap-1.5 px-3 mx-auto font-semibold text-xs shadow-sm">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="px-4 py-10 text-center text-sm text-slate-500">
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
                <textarea name="description" id="descInput" rows="3"
                    class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-slate-400 focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('descModal')"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                <button type="submit"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Simpan</button>
            </div>
        </form>
    </x-modal>


    <x-modal id="detailModal" title="Info Detail Transaksi" size="lg">
        <div id="detailContent" class="space-y-6">
            <!-- Rendered via JS -->
        </div>
    </x-modal>

    <script>
        const reportData = @json($reportRows->items());

        function openDetailModal(id) {
            const data = reportData.find(r => r.transaction_id === id);
            if (!data) return;

            const rp = (num) => 'Rp ' + Number(num).toLocaleString('id-ID');

            let html = `
            <div class="grid grid-cols-2 gap-4 text-sm bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div><span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-1">Tanggal</span> <span class="font-semibold text-slate-900">${data.transaction_date || '-'}</span></div>
                <div><span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-1">Seller</span> <span class="font-semibold text-slate-900">${data.seller_name || '-'}</span></div>
                <div class="col-span-2 border-t border-slate-200 mt-2 pt-3"></div>
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-1">Info Customer</span> 
                    <span class="font-semibold text-slate-900 block">${data.customer_name || '-'}</span>
                    <span class="text-slate-600 block mt-0.5">${data.customer_phone || '-'}</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-1">Alamat</span> 
                    <p class="text-slate-700 leading-relaxed">${data.customer_address || '-'}</p>
                </div>
            </div>
            
            <div class="mt-5 text-sm bg-slate-50 p-4 rounded-xl border border-slate-100">
                ${generateProductsHtml(data)}
            </div>

            <div class="mt-6 text-sm">
                <h4 class="font-bold text-slate-800 text-base mb-3 border-b border-slate-100 pb-2">Rincian Finansial</h4>
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                    <div class="grid grid-cols-2 gap-y-3 gap-x-6">
                        <div class="flex justify-between border-b border-emerald-200/50 pb-1.5">
                            <span class="text-slate-600 font-medium">Total Modal</span> 
                            <span class="font-semibold text-slate-800">${rp(data.modal_total)}</span>
                        </div>
                        <div class="flex justify-between border-b border-emerald-200/50 pb-1.5">
                            <span class="text-slate-600 font-medium">Total Jual</span> 
                            <span class="font-bold text-slate-900">${rp(data.selling_total)}</span>
                        </div>
                        <div class="flex justify-between border-b border-emerald-200/50 pb-1.5">
                            <span class="text-slate-600 font-medium">Biaya Tambahan</span> 
                            <span class="font-bold text-sky-600">${rp(data.service_total)}</span>
                        </div>
                        <div class="flex justify-between border-b border-emerald-200/50 pb-1.5">
                            <span class="text-slate-600 font-medium">Profit Kotor</span> 
                            <span class="font-black ${data.gross_profit_total >= 0 ? 'text-emerald-600' : 'text-rose-600'}">${rp(data.gross_profit_total)}</span>
                        </div>
                        <div class="flex justify-between border-b border-emerald-200/50 pb-1.5">
                            <span class="text-slate-600 font-medium">Laba Penjual (70%)</span> 
                            <span class="font-bold text-teal-700">${rp(data.seller_profit_share)}</span>
                        </div>
                        <div class="flex justify-between border-b border-emerald-200/50 pb-1.5">
                            <span class="text-slate-600 font-medium">Laba NATOPC (30%)</span> 
                            <span class="font-bold text-indigo-700">${rp(data.natopc_profit_share)}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 grid grid-cols-1 gap-4 text-sm">
                <div class="bg-amber-50 rounded-xl p-4 border border-amber-200/60 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 text-amber-200/40 opacity-50"><i class="fas fa-sticky-note text-6xl"></i></div>
                    <span class="text-amber-800 font-black block mb-2 relative z-10"><i class="fas fa-edit mr-1.5"></i> Catatan Lengkap (Desc)</span> 
                    <p class="text-amber-900 text-xs leading-relaxed whitespace-pre-wrap relative z-10">${data.transaction_desc || '- Belum ada catatan -'}</p>
                </div>
            </div>
        `;

            document.getElementById('detailContent').innerHTML = html;
            document.getElementById('detailModal').classList.remove('hidden');
            document.getElementById('detailModal').classList.add('flex');
        }

        function generateProductsHtml(data) {
            let partsHtml = '';
            let wParts = String(data.warranty_details_list || '').split('<br>');

            if (data.transaction_mode === 'rakit_pc') {
                let specs = String(data.item_specification || '').split(', ');

                partsHtml = `<div class="mb-4 border-b border-slate-200/60 pb-4 flex justify-between items-start gap-4">
                <div class="flex-1">
                    <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-1">Nama Build PC (Rakit)</span> 
                    <p class="font-black text-indigo-900 text-base leading-relaxed">${data.product_name || '-'}</p>
                </div>
                <div class="w-1/4 text-right bg-indigo-100/50 p-2.5 rounded-xl border border-indigo-100">
                    <span class="text-indigo-600 block text-[10px] uppercase font-bold tracking-wider mb-1">Total Qty</span> 
                    <span class="font-black text-2xl text-indigo-900">${data.quantity}</span>
                </div>
            </div>
            `;

                if (specs.length === 0 || (specs.length === 1 && specs[0] === '-')) {
                    partsHtml += `<p class="text-sm text-slate-500 italic">Tidak ada rincian komponen</p>`;
                } else {
                    partsHtml +=
                        `<span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-2 mt-4 inline-flex items-center gap-1.5"><i class="fas fa-list text-slate-400"></i> Rincian Komponen & Garansi</span><ul class="space-y-4">`;
                    specs.forEach((spec, idx) => {
                        let wr = wParts[idx] && wParts[idx] !== 'Kosong' ? wParts[idx] : '-';
                        partsHtml += `
                        <li class="flex items-start gap-3 border-b border-slate-200/60 pb-3 last:border-0 last:pb-0">
                            <span class="text-slate-500 font-bold block pt-0.5 w-4 shrink-0 text-sm">${idx + 1}.</span>
                            <div class="flex-1">
                                <p class="text-slate-800 text-[13px] font-semibold leading-relaxed break-words">${spec.trim()}</p>
                                <p class="text-[11px] text-slate-500 mt-1 inline-flex items-center gap-1"><i class="fas fa-shield-alt text-slate-400"></i> Garansi: <span class="font-medium text-slate-700">${wr}</span></p>
                            </div>
                        </li>
                    `;
                    });
                    partsHtml += `</ul>`;
                }
            } else {
                let items = String(data.product_name || '').split(', ');
                let specs = String(data.item_specification || '').split(' | ');

                partsHtml = `
            <div class="flex justify-between items-center mb-4">
                <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider">Daftar Barang & Garansi (Sparepart)</span>
                <span class="bg-indigo-100/50 text-indigo-800 px-3 py-1 flex items-center gap-2 rounded-full text-[10px] uppercase font-bold tracking-wider border border-indigo-200/60">Qty Total: <span class="text-sm font-black text-indigo-900">${data.quantity}</span></span>
            </div>
            <ul class="space-y-4">`;

                items.forEach((item, idx) => {
                    let sp = specs[idx] || '-';
                    let wr = wParts[idx] && wParts[idx] !== 'Kosong' ? wParts[idx] : '-';

                    partsHtml += `
                    <li class="flex items-start gap-3 border-b border-slate-200/60 pb-3 last:border-0 last:pb-0">
                        <span class="text-slate-500 font-bold block pt-0.5 w-4 shrink-0 text-sm">${idx + 1}.</span>
                        <div class="flex-1">
                            <p class="text-slate-800 text-[14px] font-bold leading-snug break-words">${item.trim()}</p>
                            ${sp !== '-' ? `<p class="text-[12px] text-slate-600 mt-0.5 leading-relaxed break-words"><span class="font-medium">Spec:</span> ${sp.trim()}</p>` : ''}
                            <p class="text-[11px] text-slate-500 mt-1.5 inline-flex items-center gap-1"><i class="fas fa-shield-alt text-slate-400"></i> Garansi: <span class="font-medium text-slate-700">${wr}</span></p>
                        </div>
                    </li>
                `;
                });
                partsHtml += `</ul>`;
            }

            return partsHtml;
        }

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
