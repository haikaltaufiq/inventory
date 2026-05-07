@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $trendMap = [
            'positive' => 'bg-emerald-50 text-emerald-700',
            'negative' => 'bg-rose-50 text-rose-700',
            'neutral' => 'bg-slate-100 text-slate-600',
        ];
        $trendIcons = [
            'up' => 'fa-arrow-trend-up',
            'down' => 'fa-arrow-trend-down',
            'flat' => 'fa-minus',
        ];
    @endphp

    <div class="px-4 pb-6 lg:px-5">
        <div class="mb-4 flex flex-col gap-1.5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900 lg:text-2xl">Dashboard</h1>
                <p class="mt-1 text-[13px] text-slate-500">Ringkasan performa transaksi, revenue, dan inventori terbaru.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats['kpis'] as $card)
                <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm lg:p-4">
                    <p class="text-[10px] font-medium uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p>
                    <div class="mt-2 flex items-start justify-between gap-2.5">
                        <h3 class="text-lg font-semibold text-slate-900 lg:text-xl">{{ $card['value'] }}</h3>
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full {{ $trendMap[$card['trend']['state']] ?? $trendMap['neutral'] }}">
                            <i
                                class="fa-solid {{ $trendIcons[$card['trend']['direction']] ?? $trendIcons['flat'] }} text-[10px]"></i>
                        </span>
                    </div>
                    <div class="mt-2.5 flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-medium {{ $trendMap[$card['trend']['state']] ?? $trendMap['neutral'] }}">
                            {{ $card['trend']['label'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:col-span-2 lg:p-5">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 lg:text-base">Sales Trend</h2>
                        <p class="mt-1 text-[11px] text-slate-500">Revenue dan transaksi 6 bulan terakhir.</p>
                    </div>
                    <div class="text-right text-[11px] text-slate-500">
                        <div>{{ $stats['current_month_label'] }}</div>
                        <div class="mt-1 font-medium text-slate-700">
                            {{ number_format($stats['current_month_transactions']) }} trx</div>
                    </div>
                </div>

                <x-chart id="salesOverviewChart" type="line" :height="220" :colors="['#0f766e', '#2563eb']" :series="[
                    ['name' => 'Revenue', 'type' => 'area', 'data' => $stats['revenue_series']],
                    ['name' => 'Transactions', 'type' => 'line', 'data' => $stats['transaction_series']],
                ]"
                    :categories="$stats['chart_labels']" />
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:p-5">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 lg:text-base">Inventory Mix</h2>
                        <p class="mt-1 text-[11px] text-slate-500">Distribusi stok per kategori.</p>
                    </div>
                    <div class="text-right text-[11px] text-slate-500">
                        <div>Total</div>
                        <div class="mt-1 font-medium text-slate-700">{{ number_format($stats['inventory_total_stock']) }}
                        </div>
                    </div>
                </div>

                <x-pie-chart id="inventoryDistributionChart" :series="$stats['inventory_series']" :labels="$stats['inventory_labels']" />
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:p-5">
            <div class="mb-3">
                <h2 class="text-sm font-semibold text-slate-900 lg:text-base">Recent Transactions</h2>
                <p class="mt-1 text-[11px] text-slate-500">Transaksi terbaru dari database.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-160 text-[13px]">
                    <thead class="border-b border-slate-200 text-[10px] uppercase tracking-[0.12em] text-slate-400">
                        <tr>
                            <th class="py-3 text-left font-medium">Invoice</th>
                            <th class="py-3 text-left font-medium">Customer</th>
                            <th class="py-3 text-left font-medium">Items</th>
                            <th class="py-3 text-left font-medium">Date</th>
                            <th class="py-3 text-right font-medium">Total</th>
                            <th class="py-3 text-center font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-[13px] text-slate-700">
                        @forelse($stats['recent_transactions'] as $transaction)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 font-semibold text-slate-900">{{ $transaction['invoice'] }}</td>
                                <td>{{ $transaction['customer'] }}</td>
                                <td class="text-slate-500">{{ $transaction['items'] }}</td>
                                <td>{{ $transaction['date'] }}</td>
                                <td class="text-right font-semibold text-slate-900">Rp
                                    {{ number_format($transaction['total'], 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium
                                        {{ $transaction['status'] === 'Completed'
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : ($transaction['status'] === 'Pending'
                                                ? 'bg-amber-50 text-amber-700'
                                                : 'bg-slate-100 text-slate-600') }}">
                                        {{ $transaction['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-sm text-slate-400">Belum ada transaksi
                                    untuk ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
