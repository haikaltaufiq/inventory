<?php

namespace App\Services;

use App\Exports\ExportProductStockReport;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductReportService
{
    public function getReportData(Request $request): array
    {
        $categories = Category::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $pemodals = User::query()
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        $reportQuery = $this->buildProductStockReportQuery($request);

        $summary = DB::query()
            ->fromSub(clone $reportQuery, 'report_rows')
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('COUNT(DISTINCT pemodal_user_id) as total_pemodal')
            ->selectRaw('COALESCE(SUM(CASE WHEN pemodal_user_id IS NULL THEN 1 ELSE 0 END), 0) as total_tanpa_pemodal')
            ->selectRaw('COALESCE(SUM(stock_awal), 0) as total_stock_awal')
            ->selectRaw('COALESCE(SUM(sold_qty), 0) as total_terjual')
            ->selectRaw('COALESCE(SUM(stock_ready), 0) as total_stock_ready')
            ->selectRaw('COALESCE(SUM(total_modal), 0) as total_modal')
            ->first();

        $reportRows = (clone $reportQuery)
            ->orderBy('pemodal_users.name')
            ->orderBy('products.name')
            ->orderBy('product_supplier.id')
            ->paginate(15)
            ->withQueryString();

        $reportRows->setCollection($this->withSellerBreakdown($reportRows->getCollection()));

        return [
            'reportRows' => $reportRows,
            'summary' => [
                'total_rows' => (int) ($summary->total_rows ?? 0),
                'total_pemodal' => (int) ($summary->total_pemodal ?? 0),
                'total_tanpa_pemodal' => (int) ($summary->total_tanpa_pemodal ?? 0),
                'total_stock_awal' => (int) ($summary->total_stock_awal ?? 0),
                'total_terjual' => (int) ($summary->total_terjual ?? 0),
                'total_stock_ready' => (int) ($summary->total_stock_ready ?? 0),
                'total_modal' => (float) ($summary->total_modal ?? 0),
            ],
            'categories' => $categories,
            'pemodals' => $pemodals,
        ];
    }

    public function downloadReport(Request $request): Response
    {
        $fileName = 'laporan-stok-pemodal-' . now()->format('Ymd-His') . '.xlsx';
        $rows = $this->withSellerBreakdown(
            $this->buildProductStockReportQuery($request)
                ->orderBy('pemodal_users.name')
                ->orderBy('products.name')
                ->orderBy('product_supplier.id')
                ->get()
        );

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($rows) {
            echo Excel::raw(new ExportProductStockReport($rows), \Maatwebsite\Excel\Excel::XLSX);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function buildProductStockReportQuery(Request $request)
    {
        $reportQuery = DB::table('product_supplier')
            ->join('products', 'product_supplier.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->join('suppliers', 'product_supplier.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users as pemodal_users', 'product_supplier.pemodal_user_id', '=', 'pemodal_users.id')
            ->leftJoin('transaction_details', 'transaction_details.product_supplier_id', '=', 'product_supplier.id')
            ->select([
                'product_supplier.id as product_supplier_id',
                'product_supplier.product_id',
                'product_supplier.supplier_id',
                'product_supplier.pemodal_user_id',
                'pemodal_users.name as pemodal_name',
                'pemodal_users.role as pemodal_role',
                'products.name as product_name',
                'suppliers.nama_supplier as supplier_name',
                'categories.name as category_name',
                'products.category_id',
                'product_supplier.harga_beli as modal',
                'product_supplier.harga_jual_manual as harga_jual',
                'product_supplier.stock as stock_ready',
                'product_supplier.condition',
                'product_supplier.entry_date',
            ])
            ->selectRaw('COALESCE(SUM(transaction_details.quantity), 0) as sold_qty')
            ->selectRaw('(product_supplier.stock + COALESCE(SUM(transaction_details.quantity), 0)) as stock_awal')
            ->selectRaw('(product_supplier.harga_beli * (product_supplier.stock + COALESCE(SUM(transaction_details.quantity), 0))) as total_modal')
            ->groupBy([
                'product_supplier.id',
                'product_supplier.product_id',
                'product_supplier.supplier_id',
                'product_supplier.pemodal_user_id',
                'pemodal_users.name',
                'pemodal_users.role',
                'products.name',
                'suppliers.nama_supplier',
                'categories.name',
                'products.category_id',
                'product_supplier.harga_beli',
                'product_supplier.harga_jual_manual',
                'product_supplier.stock',
                'product_supplier.condition',
                'product_supplier.entry_date',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $reportQuery->where(function ($query) use ($search) {
                $query
                    ->where('products.name', 'like', "%{$search}%")
                    ->orWhere('categories.name', 'like', "%{$search}%")
                    ->orWhere('suppliers.nama_supplier', 'like', "%{$search}%")
                    ->orWhere('pemodal_users.name', 'like', "%{$search}%")
                    ->orWhereExists(function ($sellerQuery) use ($search) {
                        $sellerQuery
                            ->select(DB::raw(1))
                            ->from('transaction_details as seller_transaction_details')
                            ->join('transactions as seller_transactions', 'seller_transaction_details.transaction_id', '=', 'seller_transactions.id')
                            ->whereColumn('seller_transaction_details.product_supplier_id', 'product_supplier.id')
                            ->where('seller_transactions.sales_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('category_id')) {
            $reportQuery->where('products.category_id', $request->integer('category_id'));
        }

        $pemodalUserId = $request->input('pemodal_user_id', $request->input('owner_id'));

        if ($pemodalUserId !== null && $pemodalUserId !== '') {
            $reportQuery->where('product_supplier.pemodal_user_id', (int) $pemodalUserId);
        }

        return $reportQuery;
    }

    private function withSellerBreakdown(Collection $rows): Collection
    {
        $productSupplierIds = $rows
            ->pluck('product_supplier_id')
            ->filter()
            ->values()
            ->all();

        $sellerBreakdownByStock = collect();

        if ($productSupplierIds !== []) {
            $sellerBreakdownByStock = DB::table('transaction_details')
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->whereIn('transaction_details.product_supplier_id', $productSupplierIds)
                ->whereNotNull('transactions.sales_name')
                ->select(
                    'transaction_details.product_supplier_id',
                    'transactions.sales_name',
                    DB::raw('SUM(transaction_details.quantity) as sold_qty')
                )
                ->groupBy('transaction_details.product_supplier_id', 'transactions.sales_name')
                ->orderBy('transactions.sales_name')
                ->get()
                ->groupBy('product_supplier_id')
                ->map(function (Collection $rows) {
                    return $rows
                        ->map(function ($row) {
                            return [
                                'name' => trim((string) $row->sales_name),
                                'qty' => (int) $row->sold_qty,
                            ];
                        })
                        ->filter(fn(array $seller) => $seller['name'] !== '' && $seller['qty'] > 0)
                        ->values();
                });
        }

        return $rows->map(function ($row) use ($sellerBreakdownByStock) {
            $directSellers = $sellerBreakdownByStock->get($row->product_supplier_id, collect());
            $sellerNames = $directSellers->pluck('name')->filter()->unique()->values();

            $row->seller_breakdown = $sellerNames
                ->map(function (string $sellerName) use ($directSellers) {
                    $seller = $directSellers->firstWhere('name', $sellerName);

                    return [
                        'name' => $sellerName,
                        'qty' => (int) ($seller['qty'] ?? 0),
                    ];
                })
                ->values();
            $row->seller_names = $sellerNames->implode(', ');

            return $row;
        });
    }
}
