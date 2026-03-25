<div class="flex items-center justify-center">
    <div id="{{ $id }}" class="w-full"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const options = {
                chart: {
                    type: 'donut',
                    height: 260
                },

                series: @json($series),
                labels: @json($labels),

                colors: [
                    '#00E396',
                    '#008FFB',
                    '#FEB019',
                    '#94a3b8'
                ],

                stroke: {
                    width: 0
                },

                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '13px',
                                    color: '#64748b',
                                    offsetY: -4
                                },
                                value: {
                                    show: true,
                                    fontSize: '22px',
                                    fontWeight: 600,
                                    color: '#0f172a',
                                    offsetY: 4
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '12px',
                                    color: '#94a3b8',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    }
                },

                dataLabels: {
                    enabled: false
                },

                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    markers: {
                        width: 8,
                        height: 8,
                        radius: 999
                    },
                    labels: {
                        colors: '#64748b'
                    }
                },

                tooltip: {
                    theme: 'light',
                    style: {
                        fontSize: '12px'
                    }
                }
            }

            new ApexCharts(
                document.querySelector('#{{ $id }}'),
                options
            ).render()
        })
    </script>
</div>
