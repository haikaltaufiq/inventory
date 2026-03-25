@extends('layouts.app')

@section('title', 'Laporan Transaksi')

@section('content')
<div class="px-5">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">
            Laporan Transaksi
        </h1>

        <a
            href="{{ route('report.download') }}"
            class="inline-flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-slate-800 transition">
            <i class="fas fa-download text-xs"></i>
            Download CSV
        </a>
    </div>

    {{-- FILTER & SEARCH (DUMMY UI) --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-6">
        <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input
                type="text"
                placeholder="Cari invoice, customer, produk..."
                class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-200">

            <input
                type="date"
                class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl">

            <select class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl">
                <option>Status</option>
                <option>Completed</option>
                <option>Pending</option>
            </select>

            <button
                type="button"
                class="bg-slate-900 text-white rounded-xl text-sm hover:bg-slate-800 transition">
                Terapkan Filter
            </button>
        </form>
    </div>

    {{-- KPI SUMMARY --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Transaksi</p>
            <h3 class="text-2xl font-semibold mt-1">
                {{ $transactions->count() }}
            </h3>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Revenue</p>
            <h3 class="text-2xl font-semibold mt-1">
                Rp {{ number_format($totalRevenue) }}
            </h3>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-gray-500">Rata-rata Transaksi</p>
            <h3 class="text-2xl font-semibold mt-1">
                Rp {{ number_format(
                    $transactions->count() > 0
                        ? $totalRevenue / $transactions->count()
                        : 0
                ) }}
            </h3>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-6 py-4 text-left font-medium">No</th>
                    <th class="px-6 py-4 text-left font-medium">Tanggal</th>
                    <th class="px-6 py-4 text-left font-medium">Customer</th>
                    <th class="px-6 py-4 text-left font-medium">Produk</th>
                    <th class="px-6 py-4 text-left font-medium">Qty</th>
                    <th class="px-6 py-4 text-left font-medium">Total</th>
                    <th class="px-6 py-4 text-left font-medium">Tipe</th>
                    <th class="px-6 py-4 text-left font-medium">Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach($transactions as $i => $t)
                <tr class="hover:bg-slate-50 transition border-b border-gray-200">
                    <td class="px-6 py-4">{{ $i + 1 }}</td>
                    <td class="px-6 py-4">
                        {{ $t->transaction_date->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $t->customer->name }}
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $t->product->name }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $t->quantity }}
                    </td>
                    <td class="px-6 py-4 font-medium">
                        Rp {{ number_format($t->total_price) }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs bg-slate-100 text-slate-600">
                            {{ $t->type }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium
                            {{ $t->status === 'Completed'
                                ? 'bg-green-100 text-green-600'
                                : 'bg-yellow-100 text-yellow-600'
                            }}">
                            {{ $t->status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection