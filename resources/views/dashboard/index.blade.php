@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="px-5 pb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Dashboard Overview</h1>
            <p class="text-sm text-slate-500 mt-1">Ringkasan performa transaksi, revenue, dan kondisi stok terbaru.</p>
        </div>
        <div class="text-xs text-slate-400">
            Updated: {{ now()->format('d M Y H:i') }}
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Transactions</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">{{ number_format($stats['total_transactions']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Revenue</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Products</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">{{ number_format($stats['total_products']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Low Stock Items</p>
            <h3 class="text-2xl font-semibold mt-2 {{ $stats['low_stock_items'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                {{ number_format($stats['low_stock_items']) }}
            </h3>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 xl:col-span-2">
            <h2 class="text-base font-semibold text-slate-800 mb-3">Sales Trend (Last 6 Months)</h2>
            <x-chart
                id="salesOverviewChart"
                type="area"
                :height="280"
                :series="[
                    ['name' => 'Revenue', 'data' => $stats['revenue_series']],
                    ['name' => 'Transactions', 'data' => $stats['transaction_series']],
                ]"
                :categories="$stats['chart_labels']" />
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <h2 class="text-base font-semibold text-slate-800 mb-3">Inventory Distribution</h2>
            <x-pie-chart
                id="inventoryDistributionChart"
                :series="$stats['inventory_series']"
                :labels="$stats['inventory_labels']" />
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 mt-6">
        <h2 class="text-base font-semibold text-slate-800 mb-4">Recent Transactions</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="text-left py-3">Invoice</th>
                        <th class="text-left py-3">Customer</th>
                        <th class="text-left py-3">Items</th>
                        <th class="text-left py-3">Date</th>
                        <th class="text-right py-3">Total</th>
                        <th class="text-center py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($stats['recent_transactions'] as $transaction)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="py-3 font-medium">{{ $transaction['invoice'] }}</td>
                        <td>{{ $transaction['customer'] }}</td>
                        <td class="text-slate-500">{{ $transaction['items'] }}</td>
                        <td>{{ $transaction['date'] }}</td>
                        <td class="text-right font-semibold">Rp {{ number_format($transaction['total'], 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="px-2.5 py-1 text-xs rounded-full
                                {{ $transaction['status'] === 'Completed'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : ($transaction['status'] === 'Pending'
                                        ? 'bg-amber-100 text-amber-700'
                                        : 'bg-slate-100 text-slate-600') }}">
                                {{ $transaction['status'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">Belum ada transaksi untuk ditampilkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
