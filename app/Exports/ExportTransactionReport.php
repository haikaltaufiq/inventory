<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        'HARGA JUAL',
        'INSTALL',
        'JASA',
        'KURIR',
        'MAKELAR',
        'TOTAL PROFIT',
        'PENJUAL',
        'NATOPC',
        'STATUS',
        'CATATAN',
        'GARANSI',
    ];

    private array $transactionRanges = [];

    public function __construct(
        private readonly string $sellerName,
        private readonly Collection $rows,
        private readonly array $filters = []
    ) {
    }

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
        $dataRows = [
            array_fill(0, 20, null),
            array_fill(0, 20, null),
            array_fill(0, 20, null),
            array_fill(0, 20, null),
            array_merge(
                [$this->titleLabel(), null, null, null, null, null, null],
                ['MODAL', 'TOTAL MODAL', 'TOTAL OMSET', 'TOTAL INSTALL', 'TOTAL JASA', 'TOTAL ONGKIR', 'TOTAL MARKETING', 'GROSS PROFIT', 'PENJUAL', 'NATOPC', 'PROFIT KOTOR', $summary['gross_profit'], 'persentase %']
            ),
            array_merge(
                array_fill(0, 7, null),
                [$summary['modal'], $summary['total_modal'], $summary['omset'], $summary['install'], $summary['jasa'], $summary['kurir'], $summary['marketing'], $summary['gross_profit'], $summary['penjual'], $summary['natopc'], null, null, $summary['percent']]
            ),
            self::HEADINGS,
        ];

        $excelRow = 8;
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
                    $isFirst ? $totals['selling'] : null,
                    $isFirst ? $totals['install'] : null,
                    $isFirst ? $totals['jasa'] : null,
                    $isFirst ? $totals['kurir'] : null,
                    $isFirst ? $totals['marketing'] : null,
                    $isFirst ? $totals['profit'] : null,
                    $isFirst ? $totals['seller'] : null,
                    $isFirst ? $totals['natopc'] : null,
                    $isFirst ? $this->statusLabel($row->status ?? null) : null,
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

        return $dataRows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(7, $sheet->getHighestRow());
                $lastCol = 'T';

                $sheet->mergeCells('A5:G6');
                $sheet->getStyle('A5:T6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0000FF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle('A7:T7')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFD966']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A5:{$lastCol}{$lastRow}")->applyFromArray([
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A8:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D8:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                $sheet->getStyle("G8:G{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("T8:T{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("H6:Q{$lastRow}")->getNumberFormat()->setFormatCode('"Rp"#,##0');
                $sheet->getStyle("S5:S5")->getNumberFormat()->setFormatCode('"Rp"#,##0');
                $sheet->getStyle("T6:T6")->getNumberFormat()->setFormatCode('0.00');

                foreach ($this->transactionRanges as [$startRow, $endRow, $isRakitPc]) {
                    $mergedColumns = ['A', 'B', 'E', 'F', 'G', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];

                    if ($isRakitPc) {
                        $mergedColumns[] = 'C';
                    }

                    foreach ($mergedColumns as $column) {
                        $sheet->mergeCells("{$column}{$startRow}:{$column}{$endRow}");
                    }
                }

                foreach ([
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
                    'R' => 14,
                    'S' => 20,
                    'T' => 24,
                ] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                for ($row = 8; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }

                $sheet->freezePane('A8');
            },
        ];
    }

    private function buildSummary(Collection $transactions): array
    {
        $totals = ['modal' => 0, 'selling' => 0, 'install' => 0, 'jasa' => 0, 'kurir' => 0, 'marketing' => 0, 'profit' => 0, 'seller' => 0, 'natopc' => 0];

        foreach ($transactions as $transactionRows) {
            $transactionTotals = $this->transactionTotals($transactionRows);
            foreach ($totals as $key => $value) {
                $totals[$key] += $transactionTotals[$key];
            }
        }

        return [
            'modal' => $totals['modal'],
            'total_modal' => $totals['modal'],
            'omset' => $totals['selling'],
            'install' => $totals['install'],
            'jasa' => $totals['jasa'],
            'kurir' => $totals['kurir'],
            'marketing' => $totals['marketing'],
            'gross_profit' => $totals['profit'],
            'penjual' => $totals['seller'],
            'natopc' => $totals['natopc'],
            'percent' => $totals['selling'] > 0 ? round(($totals['profit'] / $totals['selling']) * 100, 2) : 0,
        ];
    }

    private function transactionTotals(Collection $rows): array
    {
        $first = $rows->first();
        $modal = $rows->sum(fn($row) => (float) ($row->modal_line ?? 0));
        $selling = $rows->sum(fn($row) => (float) ($row->selling_line ?? 0));
        $install = (float) ($first->installation_fee ?? 0);
        $jasa = (float) ($first->service_labor_fee ?? $first->service_fee ?? 0);
        $kurir = (float) ($first->shipping_fee ?? 0);
        $marketing = (float) ($first->marketing_fee ?? 0);
        $profit = $selling - $modal - $install - $jasa - $kurir - $marketing;

        return [
            'modal' => $modal,
            'selling' => $selling,
            'install' => $install,
            'jasa' => $jasa,
            'kurir' => $kurir,
            'marketing' => $marketing,
            'profit' => $profit,
            'seller' => round($profit * 0.75, 2),
            'natopc' => round($profit * 0.25, 2),
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

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'Completed' => 'LUNAS',
            'Pending' => 'BELUM LUNAS',
            'Cancelled' => 'BATAL',
            default => $status ?: '-',
        };
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
