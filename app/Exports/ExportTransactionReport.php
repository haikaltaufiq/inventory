<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportTransactionReport implements WithMultipleSheets
{
    private Collection $rows;

    public function __construct($query, private readonly array $filters = [])
    {
        $this->rows = $query
            ->orderBy('seller_name')
            ->orderBy('transaction_date')
            ->orderBy('transaction_id')
            ->orderBy('transaction_detail_id')
            ->get();
    }

    public function sheets(): array
    {
        if ($this->rows->isEmpty()) {
            return [new TransactionSellerSheet('Tanpa Data', collect(), $this->filters)];
        }

        return $this->rows
            ->groupBy(fn($row) => $this->normalizeSellerName($row->seller_name ?? null))
            ->map(fn(Collection $rows, string $seller) => new TransactionSellerSheet($seller, $rows, $this->filters))
            ->values()
            ->all();
    }

    private function normalizeSellerName(?string $sellerName): string
    {
        $sellerName = trim((string) $sellerName);

        return $sellerName !== '' ? $sellerName : 'Tanpa Seller';
    }
}

class TransactionSellerSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private const SUMMARY_ROW = 14;
    private const SUMMARY_VALUE_ROW = 15;
    private const HEADING_ROW = 16;
    private const DATA_START_ROW = 17;

    private const HEADINGS = [
        'No',
        'Date',
        'Nama barang',
        'SPESIFIKASI',
        'Nama Customer',
        'Contact Cust',
        'ALAMAT',
        'MODAL',
        'TOTAL MODAL',
        'SUBTOTAL',
        'DISC',
        'HARGA JUAL',
        'INSTALL',
        'JASA',
        'TOTAL PROFIT',
        'PENJUAL',
        'NATOPC',
        'CATATAN',
        'GARANSI',
    ];

    private array $transactionRanges = [];
    private int $lastDataRow = self::HEADING_ROW;
    private int $realModalHeaderRow = 0;
    private int $realModalValueRow = 0;

    public function __construct(
        private readonly string $sellerName,
        private readonly Collection $rows,
        private readonly array $filters = []
    ) {}

    public function title(): string
    {
        $title = 'Sheet ' . $this->sellerName;
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $title);
        $title = trim(preg_replace('/\\s+/', ' ', (string) $title));

        return mb_substr($title !== '' ? $title : 'Sheet Seller', 0, 31);
    }

    public function array(): array
    {
        $this->transactionRanges = [];

        $transactions = $this->rows
            ->groupBy('transaction_id')
            ->values();

        $summary = $this->buildSummary($transactions);
        $dataRows = array_fill(0, self::SUMMARY_ROW - 1, array_fill(0, 26, null));
        $dataRows[] = array_merge(
            [$this->titleLabel(), null, null, null, null, null, null],
            ['MODAL', 'TOTAL MODAL', 'TOTAL SUBTOTAL', 'TOTAL DISC', 'TOTAL OMSET', 'TOTAL INSTALL', 'TOTAL JASA', 'GROSS PROFIT', 'PROFIT PENJUAL', 'PROFIT NATOPC', $summary['gross_profit'], 'persentase %'],
            array_fill(0, 7, null)
        );
        $dataRows[] = array_merge(
            array_fill(0, 7, null),
            [$summary['modal'], $summary['total_modal'], $summary['subtotal'], $summary['discount'], $summary['omset'], $summary['install'], $summary['jasa'], $summary['gross_profit'], $summary['penjual'], $summary['natopc'], null, $summary['percent']],
            array_fill(0, 7, null)
        );
        $dataRows[] = array_merge(self::HEADINGS, array_fill(0, 7, null));

        $excelRow = self::DATA_START_ROW;
        foreach ($transactions as $index => $transactionRows) {
            $first = $transactionRows->first();
            $count = max(1, $transactionRows->count());
            $startRow = $excelRow;
            $totals = $this->transactionTotals($transactionRows);

            foreach ($transactionRows as $detailIndex => $row) {
                $isFirst = $detailIndex === 0;
                $dataRows[] = [
                    $isFirst ? $index + 1 : null,
                    $isFirst ? $this->formatDate($row->transaction_date ?? null) : null,
                    $this->productName($row, $isFirst),
                    $this->specification($row),
                    $isFirst ? ($row->customer_name ?: '-') : null,
                    $isFirst ? ($row->customer_phone ?: '-') : null,
                    $isFirst ? ($row->customer_address ?: '-') : null,
                    (float) ($row->harga_beli ?? 0),
                    $isFirst ? $totals['modal'] : null,
                    $isFirst ? $totals['subtotal'] : null,
                    $isFirst ? $totals['discount'] : null,
                    $isFirst ? $totals['selling'] : null,
                    $isFirst ? $totals['install'] : null,
                    $isFirst ? $totals['jasa'] : null,
                    $isFirst ? $totals['profit'] : null,
                    $isFirst ? $totals['seller'] : null,
                    $isFirst ? $totals['natopc'] : null,
                    $isFirst ? ($row->transaction_description ?: '-') : null,
                    $isFirst ? ($row->warranty_detail ?: '-') : null,
                ];
                $excelRow++;
            }

            if ($count > 1) {
                $this->transactionRanges[] = [
                    $startRow,
                    $excelRow - 1,
                    ($first->transaction_mode ?? null) === 'rakit_pc',
                ];
            }
        }

        $this->lastDataRow = max(self::HEADING_ROW, $excelRow - 1);
        $this->realModalHeaderRow = $this->lastDataRow + 6;
        $this->realModalValueRow = $this->realModalHeaderRow + 1;

        while (count($dataRows) < $this->realModalHeaderRow - 1) {
            $dataRows[] = array_fill(0, 26, null);
        }

        $dataRows[] = array_merge(array_fill(0, 7, null), ['HASIL RILL TOTAL MODAL', null], array_fill(0, 17, null));
        $dataRows[] = array_merge(array_fill(0, 7, null), [$summary['modal'], $summary['total_modal']], array_fill(0, 17, null));

        return $dataRows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastTableRow = max(self::HEADING_ROW, $this->lastDataRow);

                $sheet->mergeCells('A14:F15');
                $sheet->getStyle('A14:S15')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0000FF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle('A16:S16')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFD966']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A14:S{$lastTableRow}")->applyFromArray([
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                if ($lastTableRow >= self::DATA_START_ROW) {
                    $sheet->getStyle("A17:S{$lastTableRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D17:D{$lastTableRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                    $sheet->getStyle("G17:G{$lastTableRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("S17:S{$lastTableRow}")->getAlignment()->setWrapText(true);
                }
                $sheet->getStyle("H15:Q{$lastTableRow}")->getNumberFormat()->setFormatCode('"Rp"#,##0');
                $sheet->getStyle("R14:R14")->getNumberFormat()->setFormatCode('"Rp"#,##0');
                $sheet->getStyle("S15:S15")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("H{$this->realModalValueRow}:I{$this->realModalValueRow}")->getNumberFormat()->setFormatCode('"Rp"#,##0');

                $sheet->mergeCells("H{$this->realModalHeaderRow}:I{$this->realModalHeaderRow}");
                $sheet->getStyle("H{$this->realModalHeaderRow}:I{$this->realModalValueRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                foreach ($this->transactionRanges as [$startRow, $endRow, $isRakitPc]) {
                    $mergedColumns = ['A', 'B', 'E', 'F', 'G', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];

                    if ($isRakitPc) {
                        $mergedColumns[] = 'C';
                    }

                    foreach ($mergedColumns as $column) {
                        $sheet->mergeCells("{$column}{$startRow}:{$column}{$endRow}");
                    }
                }

                foreach (
                    [
                        'A' => 6,
                        'B' => 15,
                        'C' => 18,
                        'D' => 46,
                        'E' => 18,
                        'F' => 14,
                        'G' => 28,
                        'H' => 14,
                        'I' => 14,
                        'J' => 14,
                        'K' => 12,
                        'L' => 12,
                        'M' => 12,
                        'N' => 12,
                        'O' => 14,
                        'P' => 14,
                        'Q' => 14,
                        'R' => 20,
                        'S' => 24,
                        'T' => 4,
                        'U' => 16,
                        'V' => 16,
                        'W' => 16,
                        'X' => 16,
                        'Y' => 16,
                        'Z' => 16,
                    ] as $column => $width
                ) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                if ($lastTableRow >= self::DATA_START_ROW) {
                    for ($row = self::DATA_START_ROW; $row <= $lastTableRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(18);
                    }
                }

                foreach (range(14, 24) as $row) {
                    $sheet->mergeCells("U{$row}:Z{$row}");
                }

                $sheet->setCellValue('U14', 'RUMUS PERHITUNGAN LAPORAN');
                $sheet->setCellValue('U16', 'TOTAL MODAL = jumlah modal barang');
                $sheet->setCellValue('U17', 'SUBTOTAL = harga barang sebelum disc');
                $sheet->setCellValue('U18', 'DISC = nominal diskon dari subtotal, tidak termasuk install dan jasa');
                $sheet->setCellValue('U19', 'HARGA JUAL / TOTAL OMSET = SUBTOTAL - DISC');
                $sheet->setCellValue('U20', 'TOTAL BIAYA JASA = TOTAL INSTALL + TOTAL JASA');
                $sheet->setCellValue('U21', 'TOTAL PROFIT / GROSS PROFIT = HARGA JUAL - TOTAL MODAL');
                $sheet->setCellValue('U22', 'PROFIT PENJUAL = TOTAL PROFIT x 70%');
                $sheet->setCellValue('U23', 'PROFIT NATOPC = TOTAL PROFIT x 30%');
                $sheet->getStyle('U14:Z24')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
            },
        ];
    }

    private function buildSummary(Collection $transactions): array
    {
        $totals = ['modal' => 0, 'subtotal' => 0, 'discount' => 0, 'selling' => 0, 'install' => 0, 'jasa' => 0, 'profit' => 0, 'seller' => 0, 'natopc' => 0];

        foreach ($transactions as $transactionRows) {
            $transactionTotals = $this->transactionTotals($transactionRows);
            foreach ($totals as $key => $value) {
                $totals[$key] += $transactionTotals[$key];
            }
        }

        return [
            'modal' => $totals['modal'],
            'total_modal' => $totals['modal'],
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'omset' => $totals['selling'],
            'install' => $totals['install'],
            'jasa' => $totals['jasa'],
            'gross_profit' => $totals['profit'],
            'penjual' => $totals['seller'],
            'natopc' => $totals['natopc'],
            'percent' => $totals['selling'] > 0 ? round(($totals['profit'] / $totals['selling']) * 100, 2) : 0,
        ];
    }

    private function transactionTotals(Collection $rows): array
    {
        $first = $rows->first();
        $modal = $rows->sum(fn($row) => (float) ($row->harga_beli ?? 0));
        $subtotal = $rows->sum(fn($row) => (float) ($row->subtotal_line ?? 0));
        $discount = $rows->sum(fn($row) => (float) ($row->discount_line ?? 0));
        $selling = $rows->sum(fn($row) => (float) ($row->selling_line ?? 0));
        $install = (float) ($first->installation_fee ?? 0);
        $jasa = (($first->transaction_mode ?? null) === 'rakit_pc')
            ? (float) ($first->service_labor_fee ?? 0)
            : (float) ($first->service_labor_fee ?? $first->service_fee ?? 0);
        $profit = $selling - $modal;

        return [
            'modal' => $modal,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'selling' => $selling,
            'install' => $install,
            'jasa' => $jasa,
            'profit' => $profit,
            'seller' => round($profit * 0.70, 2),
            'natopc' => round($profit * 0.30, 2),
        ];
    }

    private function titleLabel(): string
    {
        if (!empty($this->filters['date_from'])) {
            $timestamp = strtotime((string) $this->filters['date_from']);

            if ($timestamp !== false) {
                $months = [
                    1 => 'JANUARI',
                    2 => 'FEBRUARI',
                    3 => 'MARET',
                    4 => 'APRIL',
                    5 => 'MEI',
                    6 => 'JUNI',
                    7 => 'JULI',
                    8 => 'AGUSTUS',
                    9 => 'SEPTEMBER',
                    10 => 'OKTOBER',
                    11 => 'NOVEMBER',
                    12 => 'DESEMBER',
                ];

                return 'PENJUALAN ' . $months[(int) date('n', $timestamp)];
            }
        }

        return 'PENJUALAN';
    }

    private function productName(object $row, bool $isFirst): ?string
    {
        if (($row->transaction_mode ?? null) === 'rakit_pc') {
            return $isFirst
                ? ($row->item_name ?: ($row->product_line_name ?: ($row->sparepart_line_nama ?: '-')))
                : null;
        }

        return $row->product_line_name ?: ($row->sparepart_line_nama ?: ($row->item_name ?: '-'));
    }

    private function specification(object $row): string
    {
        return $row->line_specification ?: ($row->sparepart_line_nama ?: '-');
    }

    private function formatDate($date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime((string) $date);

        return $timestamp !== false ? date('j F Y', $timestamp) : (string) $date;
    }
}
