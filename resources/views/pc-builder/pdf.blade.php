<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $build->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .page {
            padding: 30px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-left {
            vertical-align: bottom;
        }
        .header-left h1 {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .header-left p {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }
        .header-right {
            text-align: right;
            vertical-align: bottom;
            font-size: 10px;
            color: #64748b;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items-table thead tr {
            background: #0f172a;
            color: #ffffff;
        }
        table.items-table th {
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.items-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        table.items-table td {
            padding: 8px 10px;
            vertical-align: top;
        }
        table.items-table td.label {
            font-weight: bold;
            color: #475569;
            width: 150px;
        }
        table.items-table td.name {
            color: #1e293b;
        }
        .badge {
            display: inline-block;
            font-size: 8px;
            background: #f1f5f9;
            color: #64748b;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: bold;
            margin-right: 4px;
        }
        .qty {
            display: inline-block;
            font-size: 8px;
            background: #ecfdf5;
            color: #047857;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: bold;
            margin-left: 4px;
        }
        .specs {
            font-size: 9px;
            color: #94a3b8;
            display: block;
            margin-top: 2px;
        }
        .total-row {
            background: #f8fafc;
            font-weight: bold;
        }
        .total-row td {
            padding: 10px;
            font-size: 12px;
        }
        .section-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .info-row {
            width: 100%;
            margin-bottom: 3px;
        }
        .info-row td {
            padding: 2px 0;
            font-size: 10px;
        }
        .info-row td.val {
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
        .notes-content {
            font-size: 10px;
            color: #334155;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    @php
        $components = $build->components ?? [];
        $pcBuilderComponents = [
            ['key' => 'cpu', 'label' => 'Processor'],
            ['key' => 'motherboard', 'label' => 'Motherboard'],
            ['key' => 'ram', 'label' => 'RAM'],
            ['key' => 'casing', 'label' => 'Casing'],
            ['key' => 'psu', 'label' => 'Power Supply'],
            ['key' => 'harddisk', 'label' => 'Hardisk'],
            ['key' => 'ssd', 'label' => 'SSD Sata / NVMe'],
            ['key' => 'vga', 'label' => 'Graphic Cards'],
            ['key' => 'assembly', 'label' => 'Jasa Rakit PC'],
            ['key' => 'monitor', 'label' => 'Monitor'],
            ['key' => 'cpu_cooler', 'label' => 'Cooler CPU'],
            ['key' => 'case_fan', 'label' => 'Fan Casing'],
            ['key' => 'os', 'label' => 'Operating System'],
            ['key' => 'mouse', 'label' => 'Mouse'],
            ['key' => 'mousepad', 'label' => 'Mousepad'],
            ['key' => 'keyboard', 'label' => 'Keyboard'],
            ['key' => 'headset', 'label' => 'Headset'],
            ['key' => 'webcam', 'label' => 'Webcam'],
            ['key' => 'networking', 'label' => 'Networking'],
            ['key' => 'ups', 'label' => 'UPS'],
        ];

        // Wattage / Power Calculations
        $cpuTdp = 0;
        if (isset($components['cpu']['specs']['tdp_watt'])) {
            $cpuTdp = intval($components['cpu']['specs']['tdp_watt']);
        }
        
        $gpuMinPsu = 0;
        if (isset($components['vga']['specs']['min_psu_watt'])) {
            $gpuMinPsu = intval($components['vga']['specs']['min_psu_watt']);
        }
        
        $totalNeed = $cpuTdp + $gpuMinPsu;
        $recommended = $totalNeed > 0 ? ceil(($totalNeed * 1.3) / 50) * 50 : 0;
        
        $psuWattage = 0;
        if (isset($components['psu']['specs']['total_wattage'])) {
            $psuWattage = intval($components['psu']['specs']['total_wattage']);
        }
    @endphp

    <div class="page">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <h1>{{ $build->name }}</h1>
                    <p>Simulasi konfigurasi dan kompatibilitas komponen PC</p>
                </td>
                <td class="header-right">
                    Dicetak: {{ $build->created_at->format('d F Y') }}
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Komponen</th>
                    <th style="width: 70%;">Rincian Item</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pcBuilderComponents as $comp)
                    @if (isset($components[$comp['key']]) && !empty($components[$comp['key']]))
                        @php
                            $p = $components[$comp['key']];
                            $qty = isset($p['qty']) && intval($p['qty']) > 0 ? intval($p['qty']) : 1;
                            $specStr = '';
                            if (isset($p['specs']) && is_array($p['specs'])) {
                                $specsArr = [];
                                $count = 0;
                                foreach ($p['specs'] as $k => $v) {
                                    if ($count >= 4) break;
                                    $specsArr[] = str_replace('_', ' ', $k) . ': ' . $v;
                                    $count++;
                                }
                                $specStr = implode(' - ', $specsArr);
                            }
                        @endphp
                        <tr>
                            <td class="label">{{ $comp['label'] }}</td>
                            <td class="name">
                                @if (!empty($p['brand']))
                                    <span class="badge">{{ $p['brand'] }}</span>
                                @endif
                                {{ $p['name'] }}
                                @if ($qty > 1)
                                    <span class="qty">x{{ $qty }}</span>
                                @endif
                                @if (!empty($specStr))
                                    <span class="specs">{{ $specStr }}</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
                <tr class="total-row">
                    <td>Total Estimasi</td>
                    <td style="font-size: 13px; color: #0f172a; text-align: left;">
                        Rp {{ number_format($build->harga_jual, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        @if ($totalNeed > 0)
            <div class="section-card">
                <div class="section-title">Estimasi Konsumsi Daya</div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr class="info-row">
                        <td style="color: #475569;">CPU TDP</td>
                        <td class="val">{{ $cpuTdp }} W</td>
                    </tr>
                    <tr class="info-row">
                        <td style="color: #475569;">GPU Min PSU</td>
                        <td class="val">{{ $gpuMinPsu }} W</td>
                    </tr>
                    <tr class="info-row" style="border-top: 1px solid #e2e8f0; margin-top: 4px; padding-top: 4px;">
                        <td style="color: #475569; font-weight: bold; padding-top: 4px;">Total Estimasi</td>
                        <td class="val" style="padding-top: 4px;">{{ $totalNeed }} W</td>
                    </tr>
                    <tr class="info-row">
                        <td style="color: #475569; font-weight: bold;">Rekomendasi PSU (headroom 30%)</td>
                        <td class="val" style="color: #0f172a;">minimal {{ $recommended }} W</td>
                    </tr>
                    @if ($psuWattage > 0)
                        <tr class="info-row">
                            <td style="color: #475569;">PSU Dipilih</td>
                            <td class="val">{{ $psuWattage }} W</td>
                        </tr>
                    @endif
                </table>
            </div>
        @endif

        @if (!empty($build->notes))
            <div class="section-card">
                <div class="section-title">Catatan</div>
                <div class="notes-content">{!! nl2br(e($build->notes)) !!}</div>
            </div>
        @endif

        <div class="footer">
            Dokumen ini merupakan estimasi. Harga dapat berubah sewaktu-waktu.
        </div>
    </div>
</body>
</html>
