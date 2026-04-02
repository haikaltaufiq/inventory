@props([
    'id' => 'chart',
    'type' => 'line',
    'height' => 300,
    'series' => [],
    'categories' => [],
    'colors' => ['#0f766e', '#2563eb'],
])

<div id="{{ $id }}" style="min-height: {{ $height }}px"></div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const series = @json($series);
            const categories = @json($categories);
            const chartColors = @json($colors);
            const compactFormatter = new Intl.NumberFormat('id-ID', {
                notation: 'compact',
                maximumFractionDigits: 1,
            });
            const numberFormatter = new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0,
            });
            const formatCompactValue = (value) => {
                const numericValue = Number(value) || 0;

                if (Math.abs(numericValue) < 1000) {
                    return numberFormatter.format(Math.round(numericValue));
                }

                return compactFormatter.format(numericValue).replace(/\s/g, '');
            };
            const formatCurrencyCompact = (value) => `Rp ${formatCompactValue(value)}`;
            const formatInteger = (value) => numberFormatter.format(Math.round(Number(value) || 0));

            const options = {
                chart: {
                    type: @json($type),
                    height: {{ (int) $height }},
                    background: 'transparent',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    },
                    parentHeightOffset: 0
                },

                stroke: {
                    curve: 'smooth',
                    width: [2.25, 2.25],
                    dashArray: [0, 5]
                },

                colors: chartColors,
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.8,
                        opacityFrom: 0.16,
                        opacityTo: 0.02,
                        stops: [0, 92, 100]
                    },
                    opacity: [0.14, 1]
                },

                dataLabels: {
                    enabled: false
                },

                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4,
                    padding: {
                        left: 0,
                        right: 8
                    }
                },

                xaxis: {
                    categories: categories,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: '#94a3b8',
                            fontSize: '11px'
                        },
                    },
                    tooltip: {
                        enabled: false,
                    },
                },

                yaxis: [{
                    seriesName: 'Revenue',
                    tickAmount: 4,
                    forceNiceScale: true,
                    labels: {
                        formatter: function(value) {
                            return formatCurrencyCompact(value);
                        },
                        style: {
                            colors: '#94a3b8',
                            fontSize: '11px'
                        }
                    }
                }, {
                    seriesName: 'Transactions',
                    opposite: true,
                    tickAmount: 4,
                    min: 0,
                    forceNiceScale: true,
                    labels: {
                        formatter: function(value) {
                            return formatInteger(value);
                        },
                        style: {
                            colors: '#94a3b8',
                            fontSize: '11px'
                        }
                    }
                }],

                tooltip: {
                    theme: 'light',
                    shared: true,
                    intersect: false,
                    x: {
                        show: true
                    },
                    marker: {
                        show: true
                    },
                    style: {
                        fontSize: '11px'
                    },
                    y: {
                        formatter: function(value, { seriesIndex, w }) {
                            const seriesName = (w?.globals?.seriesNames?.[seriesIndex] || '').toLowerCase();

                            if (seriesName.includes('revenue') || seriesName.includes('omzet')) {
                                return formatCurrencyCompact(value);
                            }

                            return `${formatInteger(value)} transaksi`;
                        }
                    }
                },

                markers: {
                    size: 0,
                    strokeWidth: 0,
                    hover: {
                        size: 5
                    }
                },

                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'left',
                    fontSize: '10px',
                    offsetY: -2,
                    labels: {
                        colors: '#475569',
                    },
                    markers: {
                        width: 7,
                        height: 7,
                        radius: 999,
                    },
                },

                series: series
            };


            new ApexCharts(
                document.querySelector("#{{ $id }}"),
                options
            ).render();
        });
    </script>
@endpush
