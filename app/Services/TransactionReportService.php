<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exports\ExportTransactionReport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class TransactionReportService
{
    public function getReportData(Request $request): array
    {
        $reportQuery = $this->buildTransactionReportQuery($request);

        $summary = DB::query()
            ->fromSub(clone $reportQuery, 'report_rows')
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('COALESCE(SUM(selling_total), 0) as total_selling')
            ->selectRaw('COALESCE(SUM(service_total), 0) as total_service')
            ->selectRaw('COALESCE(SUM(gross_profit_total), 0) as total_profit')
            ->first();

        $reportRows = (clone $reportQuery)
            ->orderByDesc('transaction_date')
            ->orderByDesc('transaction_id')
            ->paginate(15)
            ->withQueryString();

        return [
            'reportRows' => $reportRows,
            'summary' => [
                'total_rows' => (int) ($summary->total_rows ?? 0),
                'total_selling' => (float) ($summary->total_selling ?? 0),
                'total_service' => (float) ($summary->total_service ?? 0),
                'total_profit' => (float) ($summary->total_profit ?? 0),
            ],
        ];
    }

    public function downloadReport(Request $request): Response
    {
        $fileName = 'laporan-transaksi-' . now()->format('Ymd-His') . '.xlsx';
        $query = $this->buildTransactionReportLineSubquery($request);
        $filters = $request->only(['date_from', 'date_to', 'search']);

        // 1. Bersihin buffer sampe ke akar-akarnya bjir
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 2. Pake download manual biar kita bisa kontrol Header-nya
        return response()->streamDownload(function () use ($query, $filters) {
            echo Excel::raw(new ExportTransactionReport($query, $filters), \Maatwebsite\Excel\Excel::XLSX);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function buildTransactionReportQuery(Request $request)
    {
        $lineSub = $this->buildTransactionReportLineSubquery($request);

        return DB::query()
            ->fromSub($lineSub, 'lines')
            ->groupBy('lines.transaction_id')
            ->select([
                DB::raw('MIN(lines.transaction_detail_id) as transaction_detail_id'),
                'lines.transaction_id',
                DB::raw('MAX(lines.seller_name) as seller_name'),
                DB::raw('MAX(lines.transaction_mode) as transaction_mode'),
                DB::raw('MAX(lines.transaction_date) as transaction_date'),
                DB::raw('MAX(lines.status) as status'),
                DB::raw('MAX(lines.customer_name) as customer_name'),
                DB::raw('MAX(lines.customer_phone) as customer_phone'),
                DB::raw('MAX(lines.customer_address) as customer_address'),
                DB::raw("CASE MAX(lines.transaction_mode)
                    WHEN 'rakit_pc' THEN MAX(lines.item_name)
                    ELSE GROUP_CONCAT(lines.sparepart_line_nama ORDER BY lines.transaction_detail_id SEPARATOR ', ')
                END as product_name"),
                DB::raw("CASE MAX(lines.transaction_mode)
                    WHEN 'rakit_pc' THEN GROUP_CONCAT(lines.line_specification ORDER BY lines.transaction_detail_id SEPARATOR ', ')
                    ELSE GROUP_CONCAT(lines.line_specification ORDER BY lines.transaction_detail_id SEPARATOR ' | ')
                END as item_specification"),
                DB::raw('SUM(lines.quantity) as quantity'),
                DB::raw('CASE WHEN SUM(lines.quantity) > 0 THEN SUM(lines.modal_line) / SUM(lines.quantity) ELSE 0 END as modal_price_unit'),
                DB::raw('CASE WHEN SUM(lines.quantity) > 0 THEN SUM(lines.selling_line) / SUM(lines.quantity) ELSE 0 END as selling_price_unit'),
                DB::raw('SUM(lines.modal_line) as modal_total'),
                DB::raw('SUM(lines.selling_line) as selling_total'),
                DB::raw('SUM(lines.service_line) as service_total'),
                DB::raw('SUM(lines.gross_line) as gross_profit_total'),
                DB::raw('SUM(lines.seller_line) as seller_profit_share'),
                DB::raw('SUM(lines.natopc_line) as natopc_profit_share'),
                DB::raw('MAX(lines.transaction_description) as transaction_desc'),
                DB::raw("GROUP_CONCAT(COALESCE(NULLIF(TRIM(lines.warranty_detail), ''), 'Kosong') ORDER BY lines.transaction_detail_id SEPARATOR '<br>') as warranty_details_list"),
            ]);
    }

    private function buildTransactionReportLineSubquery(Request $request)
    {
        $hasPaymentStatus = Schema::hasColumn('transactions', 'payment_status');
        $statusSelect = $hasPaymentStatus
            ? DB::raw("CASE WHEN t.payment_status = 'paid' THEN 'Completed' ELSE t.status END as status")
            : 't.status';

        $query = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->leftJoin('customers as c', 't.customer_id', '=', 'c.id')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->leftJoin('product_supplier as ps', 'td.product_supplier_id', '=', 'ps.id')
            ->leftJoin('product_spec_value as psv', 'p.id', '=', 'psv.product_id')
            ->leftJoin('spec_value_presets as pspec', 'psv.spec_value_preset_id', '=', 'pspec.id')
            ->select([
                'td.id as transaction_detail_id',
                'td.transaction_id',
                't.sales_name as seller_name',
                't.transaction_mode',
                't.transaction_date',
                $statusSelect,
                'c.name as customer_name',
                'c.phone as customer_phone',
                'c.address as customer_address',
                'td.item_name',
                'p.name as product_line_name',
                DB::raw('COALESCE(NULLIF(TRIM(td.item_name), \'\'), p.name) as sparepart_line_nama'),
                DB::raw("CASE
                    WHEN t.transaction_mode = 'rakit_pc' THEN p.name
                    ELSE COALESCE(
                        td.item_specification,
                        GROUP_CONCAT(CONCAT(pspec.spec_key, ': ', pspec.spec_value) SEPARATOR ', ')
                    )
                END as line_specification"),
                'td.quantity',
                'td.price_at_transaction',
                'ps.harga_beli',
                't.service_fee',
                't.discount_fee',
                't.installation_fee',
                't.service_labor_fee',
                't.shipping_fee',
                't.marketing_fee',
                't.description as transaction_description',
                'ps.warranty_detail',
            ])
            ->selectRaw('(td.quantity * COALESCE(ps.harga_beli, 0)) as modal_line')
            ->selectRaw('CASE
                WHEN (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)) > 0
                    THEN ROUND(
                        (td.quantity * COALESCE(td.price_at_transaction, 0))
                        - (COALESCE(t.discount_fee, 0) * ((td.quantity * COALESCE(td.price_at_transaction, 0)) / (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)))),
                        2
                    )
                ELSE (td.quantity * COALESCE(td.price_at_transaction, 0))
            END as selling_line')
            ->selectRaw('CASE
                WHEN COALESCE(t.subtotal, 0) > 0 AND (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)) > 0
                    THEN ROUND(
                        (COALESCE(t.service_fee, 0) * ((td.quantity * COALESCE(td.price_at_transaction, 0)) / t.subtotal))
                        - (COALESCE(t.discount_fee, 0) * ((COALESCE(t.service_fee, 0) * ((td.quantity * COALESCE(td.price_at_transaction, 0)) / t.subtotal)) / (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)))),
                        2
                    )
                ELSE 0
            END as service_line')
            ->selectRaw('(CASE
                WHEN (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)) > 0
                    THEN ROUND(
                        (td.quantity * COALESCE(td.price_at_transaction, 0))
                        - (COALESCE(t.discount_fee, 0) * ((td.quantity * COALESCE(td.price_at_transaction, 0)) / (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)))),
                        2
                    )
                ELSE (td.quantity * COALESCE(td.price_at_transaction, 0))
            END - (td.quantity * COALESCE(ps.harga_beli, 0))) as gross_line')
            ->selectRaw('ROUND(((CASE
                WHEN (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)) > 0
                    THEN ROUND(
                        (td.quantity * COALESCE(td.price_at_transaction, 0))
                        - (COALESCE(t.discount_fee, 0) * ((td.quantity * COALESCE(td.price_at_transaction, 0)) / (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)))),
                        2
                    )
                ELSE (td.quantity * COALESCE(td.price_at_transaction, 0))
            END - (td.quantity * COALESCE(ps.harga_beli, 0))) * 0.7), 2) as seller_line')
            ->selectRaw('ROUND(((CASE
                WHEN (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)) > 0
                    THEN ROUND(
                        (td.quantity * COALESCE(td.price_at_transaction, 0))
                        - (COALESCE(t.discount_fee, 0) * ((td.quantity * COALESCE(td.price_at_transaction, 0)) / (COALESCE(t.subtotal, 0) + COALESCE(t.service_fee, 0)))),
                        2
                    )
                ELSE (td.quantity * COALESCE(td.price_at_transaction, 0))
            END - (td.quantity * COALESCE(ps.harga_beli, 0))) * 0.3), 2) as natopc_line')
            ->groupBy([
                'td.id',
                'td.transaction_id',
                't.sales_name',
                't.transaction_mode',
                't.transaction_date',
                't.status',
                'c.name',
                'c.phone',
                'c.address',
                't.subtotal',
                't.service_fee',
                't.discount_fee',
                'td.item_name',
                'td.item_specification',
                'p.name',
                'td.quantity',
                'td.price_at_transaction',
                'ps.harga_beli',
                't.installation_fee',
                't.service_labor_fee',
                't.shipping_fee',
                't.marketing_fee',
                't.description',
                'ps.warranty_detail',
            ])
            ->when($hasPaymentStatus, fn ($q) => $q->groupBy('t.payment_status'));

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $like   = '%' . $search . '%';

            $query->where(function ($w) use ($like) {
                $w->where('t.sales_name', 'like', $like)
                    ->orWhere('c.name', 'like', $like)
                    ->orWhere('c.phone', 'like', $like)
                    ->orWhere('c.address', 'like', $like)
                    ->orWhere('p.name', 'like', $like)
                    ->orWhere('td.item_name', 'like', $like)
                    ->orWhere('td.item_specification', 'like', $like)
                    ->orWhereIn('td.transaction_id', function ($sub) use ($like) {
                        $sub->from('transaction_details as td2')
                            ->leftJoin('products as p2', 'p2.id', '=', 'td2.product_id')
                            ->select('td2.transaction_id')
                            ->where(function ($q) use ($like) {
                                $q->where('p2.name', 'like', $like)
                                    ->orWhere('td2.item_name', 'like', $like)
                                    ->orWhere('td2.item_specification', 'like', $like);
                            });
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('t.transaction_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('t.transaction_date', '<=', $request->input('date_to'));
        }

        return $query;
    }
}
