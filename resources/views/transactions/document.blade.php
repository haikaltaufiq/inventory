<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $document_type }} - {{ $transaction->id }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
        }

        .page {
            padding: 32px 36px;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 1px;
        }

        .subtle {
            color: #64748b;
        }

        .label {
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 2px;
            font-weight: 700;
            color: #94a3b8;
        }

        .section {
            margin-top: 18px;
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            background: #f8fafc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
        }

        .totals td {
            border: none;
            padding: 6px 8px;
        }

        .totals .label-cell {
            text-align: right;
            color: #64748b;
        }

        .totals .value-cell {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
        }

        .signature {
            margin-top: 24px;
            display: inline-block;
            border-top: 1px solid #cbd5f5;
            padding-top: 6px;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>

<body>
    {{-- CALCULATION / NORMALIZATION --}}
    @php
        $issuedAt = $issued_at ?? now();
        $transactionDate = $transaction->transaction_date
            ? \Illuminate\Support\Carbon::parse($transaction->transaction_date)
            : $issuedAt;
        $details = $transaction->details ?? collect();
        $subtotal = $transaction->subtotal ?? $details->sum(fn($d) => ($d->price_at_transaction ?? 0) * ($d->quantity ?? 0));
        $serviceFee = $transaction->service_fee ?? 0;
        $installationFee = $transaction->installation_fee ?? 0;
        $serviceLaborFee = $transaction->service_labor_fee ?? 0;
        $discountFee = $transaction->discount_fee ?? 0;
        $discountBase = $subtotal + max(0, $serviceFee - $installationFee - $serviceLaborFee);
        $discountPercent = $discountBase > 0 ? round(($discountFee / $discountBase) * 100, 2) : 0;
        $otherFee = max(0, $serviceFee - $installationFee - $serviceLaborFee);
        $finalTotal = $transaction->final_total ?? max(0, $subtotal + $serviceFee - $discountFee);
        $grandTotal = max(0, $discountBase - $discountFee);
        $showPricing = $document_type !== 'Delivery Order';
        $showItemPricing = $showPricing && empty($isPcBuilder);
    @endphp

    <div class="page">
        {{-- HEADER: BRAND + DOC META --}}
        <table>
            <tr>
                <td>
                    <div class="label">NATOPC</div>
                    <p class="title">{{ $document_type }}</p>
                    <p class="subtle">Professional Transaction Document</p>
                </td>
                <td style="text-align: right;">
                    <div class="label">Document No</div>
                    <p style="font-weight: 700; margin: 2px 0;">{{ $document_code }}-{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</p>
                    <p class="subtle">Issued: {{ $issuedAt->format('d M Y') }}</p>
                    <p class="subtle">Transaction Date: {{ $transactionDate->format('d M Y') }}</p>
                </td>
            </tr>
        </table>

        {{-- SECTION: CUSTOMER + SALES --}}
        <div class="section">
            <div class="card">
                <table>
                    <tr>
                        <td style="width: 50%;">
                            <div class="label">Billed To</div>
                            <p style="font-weight: 700; margin: 4px 0 2px;">{{ $transaction->customer?->name ?? 'Customer' }}</p>
                            <p class="subtle" style="margin: 0;">{{ $transaction->customer?->phone ?? '-' }}</p>
                            <p class="subtle" style="margin: 0;">{{ $transaction->customer?->email ?? '-' }}</p>
                            <p class="subtle" style="margin: 0;">{{ $transaction->customer?->address ?? '-' }}</p>
                        </td>
                        <td style="width: 50%;">
                            <div class="label">Sales</div>
                            <p style="font-weight: 700; margin: 4px 0 2px;">{{ $transaction->sales_name ?? '-' }}</p>
                            <p class="subtle" style="margin: 0;">Type: {{ $transaction->type ?? $document_type }}</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- SECTION: ITEMS TABLE --}}
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th style="width: 6%;">No</th>
                        <th>Item</th>
                        <th style="width: 12%; text-align: right;">Qty</th>
                        @if($showItemPricing)
                            <th style="width: 18%; text-align: right;">Unit Price</th>
                            <th style="width: 18%; text-align: right;">Subtotal</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($details as $index => $detail)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $detail->product?->name ?? 'Item' }}</td>
                            <td style="text-align: right;">{{ $detail->quantity ?? 0 }}</td>
                            @if($showItemPricing)
                                <td style="text-align: right;">Rp {{ number_format($detail->price_at_transaction ?? 0, 0, ',', '.') }}</td>
                                <td style="text-align: right;">Rp {{ number_format(($detail->price_at_transaction ?? 0) * ($detail->quantity ?? 0), 0, ',', '.') }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showItemPricing ? 5 : 3 }}" class="subtle">Tidak ada detail transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($showPricing)
            {{-- SECTION: TOTALS --}}
            <div class="section">
                <table class="totals">
                    @unless($isPcBuilder)
                        <tr>
                            <td class="label-cell" style="width: 70%;">SUB TOTAL</td>
                            <td class="value-cell">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endunless
                    @if($isPcBuilder)
                        <tr>
                            <td class="label-cell" style="width: 70%;">SUB TOTAL</td>
                            <td class="value-cell">Rp {{ number_format($discountBase, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($discountFee > 0)
                        <tr>
                            <td class="label-cell">DISC ({{ rtrim(rtrim(number_format($discountPercent, 2, ',', '.'), '0'), ',') }}%)</td>
                            <td class="value-cell">- Rp {{ number_format($discountFee, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label-cell" style="font-weight: 700;">GRAND TOTAL</td>
                        <td class="value-cell" style="font-size: 14px;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($installationFee > 0)
                        <tr>
                            <td class="label-cell">Biaya Instalasi</td>
                            <td class="value-cell">Rp {{ number_format($installationFee, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($serviceLaborFee > 0)
                        <tr>
                            <td class="label-cell">Biaya Jasa</td>
                            <td class="value-cell">Rp {{ number_format($serviceLaborFee, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($otherFee > 0 && !$isPcBuilder)
                        <tr>
                            <td class="label-cell">Detail Penyesuaian Harga</td>
                            <td class="value-cell">Rp {{ number_format($otherFee, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label-cell" style="font-weight: 700;">Total Tagihan</td>
                        <td class="value-cell" style="font-size: 14px;">Rp {{ number_format($finalTotal, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        @else
            {{-- SECTION: DELIVERY ORDER NOTE --}}
            <div class="section">
                <p class="subtle">Dokumen ini adalah Delivery Order resmi dan tidak mencantumkan harga.</p>
            </div>
        @endif

        {{-- SECTION: SIGNATURE --}}
        <div class="section">
            <div class="signature">Authorized Signature</div>
        </div>
    </div>
</body>

</html>
