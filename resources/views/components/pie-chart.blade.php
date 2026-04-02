@props([
    'id' => 'pie-chart',
    'series' => [],
    'labels' => [],
])

@php
    $palette = ['#1d4ed8', '#0f766e', '#f59e0b', '#ea580c', '#64748b', '#7c3aed'];
    $total = collect($series)->sum();
    $segments = collect($labels)
        ->map(function ($label, $index) use ($series, $palette, $total) {
            $value = (int) ($series[$index] ?? 0);

            return [
                'label' => $label,
                'value' => $value,
                'share' => $total > 0 ? round(($value / $total) * 100, 1) : 0,
                'color' => $palette[$index % count($palette)],
            ];
        })
        ->values();
@endphp

@if($segments->isEmpty() || $total === 0)
    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-7 text-center">
        <p class="text-[13px] font-medium text-slate-700">Belum ada distribusi stok</p>
        <p class="mt-1 text-xs text-slate-500">Chart akan muncul saat data stok kategori tersedia.</p>
    </div>
@else
    <div class="grid gap-3 lg:grid-cols-[minmax(0,190px)_minmax(0,1fr)] lg:items-center">
        <div class="relative">
            <div class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center text-center">
                <span class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Total</span>
                <span class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($total) }}</span>
            </div>
            <div id="{{ $id }}" class="mx-auto h-[190px] w-full max-w-[190px]"></div>
        </div>

        <div class="space-y-1.5">
            @foreach($segments as $segment)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-2.5 py-1.5">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $segment['color'] }}"></span>
                        <span class="truncate text-[13px] text-slate-700">{{ $segment['label'] }}</span>
                    </div>
                    <div class="text-right">
                        <div class="text-[13px] font-semibold text-slate-900">{{ number_format($segment['value']) }}</div>
                        <div class="text-[10px] text-slate-400">{{ rtrim(rtrim(number_format($segment['share'], 1, ',', '.'), '0'), ',') }}%</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const options = {
                    chart: {
                        type: 'donut',
                        height: 190,
                        background: 'transparent',
                    },
                    series: @json($series),
                    labels: @json($labels),
                    colors: @json($segments->pluck('color')->all()),
                    stroke: {
                        width: 3,
                        colors: ['#ffffff']
                    },
                    plotOptions: {
                        pie: {
                            expandOnClick: false,
                            donut: {
                                size: '72%',
                            }
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    legend: {
                        show: false
                    },
                    states: {
                        hover: {
                            filter: {
                                type: 'none',
                            }
                        },
                        active: {
                            filter: {
                                type: 'none',
                            }
                        }
                    },
                    tooltip: {
                        theme: 'light',
                        style: {
                            fontSize: '12px'
                        },
                        y: {
                            formatter: function(value) {
                                return `${new Intl.NumberFormat('id-ID').format(value)} unit`;
                            }
                        }
                    }
                };

                new ApexCharts(
                    document.querySelector('#{{ $id }}'),
                    options
                ).render();
            });
        </script>
    @endpush
@endif
