@props([
    'id' => 'chart',
    'type' => 'line',
    'height' => 300,
    'series' => [],
    'categories' => [],
])

<div id="{{ $id }}" style="min-height: {{ $height }}px"></div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const options = {
                chart: {
                    type: 'area',
                    height: 260,
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
                    }
                },

                stroke: {
                    curve: 'smooth',
                    width: 3
                },

                colors: ['#00E396', '#008FFB'], // hijau & biru
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },

                dataLabels: {
                    enabled: false
                },

                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4,
                    padding: {
                        left: 10,
                        right: 10
                    }
                },

                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: '#94a3b8',
                            fontSize: '12px'
                        }
                    }
                },

                yaxis: {
                    labels: {
                        style: {
                            colors: '#94a3b8',
                            fontSize: '12px'
                        }
                    }
                },

                tooltip: {
                    theme: 'light',
                    x: {
                        show: false
                    },
                    marker: {
                        show: false
                    },
                    style: {
                        fontSize: '12px'
                    }
                },

                markers: {
                    size: 0,
                    hover: {
                        size: 6
                    }
                },

                legend: {
                    show: false
                },

                series: [{
                        name: 'Sales',
                        data: [30, 25, 40, 28, 45, 50]
                    },
                    {
                        name: 'Revenue',
                        data: [20, 18, 30, 22, 40, 42]
                    }
                ]
            }


            new ApexCharts(
                document.querySelector("#{{ $id }}"),
                options
            ).render();
        });
    </script>
@endpush
