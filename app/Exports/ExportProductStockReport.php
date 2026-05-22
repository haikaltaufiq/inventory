<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportProductStockReport implements WithMultipleSheets
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function sheets(): array
    {
        if ($this->rows->isEmpty()) {
            return [new ProductOwnerStockSheet('Tanpa Data', collect())];
        }

        return $this->rows
            ->groupBy(fn($row) => $this->normalizeOwnerName($row->pemodal_name ?? null))
            ->map(fn(Collection $rows, string $owner) => new ProductOwnerStockSheet($owner, $rows))
            ->values()
            ->all();
    }

    private function normalizeOwnerName(?string $ownerName): string
    {
        $ownerName = trim((string) $ownerName);

        return $ownerName !== '' ? $ownerName : 'Tanpa Pemodal';
    }
}

class ProductOwnerStockSheet implements FromArray, WithTitle, WithEvents
{
    private const HEADINGS = [
        'Kategori Barang',
        'NAMA BARANG',
        'MODAL',
        'STOCK',
        'TERJUAL',
        'QTY',
        'CALCULATE',
        'SUPPLIER',
        'SELLER',
        'PEMODAL',
        'QC TEST',
        'DATE IN',
    ];

    public function __construct(
        private readonly string $ownerName,
        private readonly Collection $rows
    ) {
    }

    public function title(): string
    {
        $title = $this->ownerName . ' STOCK';
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $title);
        $title = trim(preg_replace('/\\s+/', ' ', (string) $title));

        return mb_substr($title !== '' ? $title : 'Owner STOCK', 0, 31);
    }

    public function array(): array
    {
        $totalCalculate = $this->rows->isEmpty()
            ? 0
            : '=SUM(G3:G' . ($this->rows->count() + 2) . ')';

        $dataRows = [
            [null, null, strtoupper($this->ownerName), null, null, null, $totalCalculate],
            self::HEADINGS,
        ];

        foreach ($this->rows->values() as $index => $row) {
            $excelRow = $index + 3;
            $stockAwal = (int) ($row->stock_awal ?? 0);
            $soldQty = (int) ($row->sold_qty ?? 0);

            $dataRows[] = [
                $row->category_name ?: 'NO INFO/MISSING',
                $row->product_name ?: '-',
                (float) ($row->modal ?? 0),
                $stockAwal,
                $soldQty,
                '=D' . $excelRow . '-E' . $excelRow,
                '=C' . $excelRow . '*F' . $excelRow,
                $row->supplier_name ?: null,
                $this->sellerLabel($row),
                $row->pemodal_name ?: $this->ownerName,
                null,
                $this->formatDate($row->entry_date ?? null),
            ];
        }

        return $dataRows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $sheet->getHighestRow());

                $sheet->freezePane('A3');
                $sheet->setAutoFilter("A2:L{$lastRow}");

                $sheet->getStyle("A1:L{$lastRow}")->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                        'color' => ['rgb' => '000000'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A1:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('B3:B' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('I3:I' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('A2:L2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '14532D'],
                    ],
                ]);

                $sheet->getStyle("C3:C{$lastRow}")->getNumberFormat()->setFormatCode('"Rp"#,##0');
                $sheet->getStyle("G1:G{$lastRow}")->getNumberFormat()->setFormatCode('"Rp"#,##0');
                $sheet->getStyle("D3:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("L3:L{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle("C3:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G1:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("A1:L{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                foreach ([
                    'A' => 20.63,
                    'B' => 59,
                    'C' => 18,
                    'D' => 14,
                    'E' => 15.63,
                    'F' => 8.63,
                    'G' => 24,
                    'H' => 20,
                    'I' => 26,
                    'J' => 16.38,
                    'K' => 12.13,
                    'L' => 11.5,
                ] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                for ($row = 1; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }
            },
        ];
    }

    private function sellerLabel(object $row): ?string
    {
        $sellerNames = collect(explode(',', (string) ($row->seller_names ?? '')))
            ->map(fn(string $sellerName) => trim($sellerName))
            ->filter()
            ->unique()
            ->values();

        if ($sellerNames->isNotEmpty()) {
            return $sellerNames->implode(', ');
        }

        return collect($row->seller_breakdown ?? [])
            ->pluck('name')
            ->map(fn($sellerName) => trim((string) $sellerName))
            ->filter()
            ->unique()
            ->implode(', ') ?: null;
    }

    private function formatDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        $timestamp = strtotime((string) $date);

        return $timestamp !== false ? date('d/m/Y', $timestamp) : (string) $date;
    }
}
