<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductReportService
{
    public function getReportData(Request $request): array
    {
        $categories = Category::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $owners = User::query()
            ->select('id', 'name')
            ->where('role', 'owner')
            ->orderBy('name')
            ->get();

        $reportQuery = DB::table('product_supplier')
            ->join('products', 'product_supplier.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->join('suppliers', 'product_supplier.supplier_id', '=', 'suppliers.id')
            ->join('users as pemodal_users', function ($join) {
                $join->on('product_supplier.pemodal_user_id', '=', 'pemodal_users.id')
                    ->where('pemodal_users.role', '=', 'owner');
            })
            ->leftJoin('transaction_details', 'transaction_details.product_supplier_id', '=', 'product_supplier.id')
            ->select([
                'product_supplier.id as product_supplier_id',
                'product_supplier.pemodal_user_id',
                'pemodal_users.name as pemodal_name',
                'products.name as product_name',
                'suppliers.nama_supplier as supplier_name',
                'categories.name as category_name',
                'products.category_id',
                'product_supplier.harga_beli as modal',
                'product_supplier.stock as stock_ready',
            ])
            ->selectRaw('COALESCE(SUM(transaction_details.quantity), 0) as sold_qty')
            ->selectRaw('(product_supplier.stock + COALESCE(SUM(transaction_details.quantity), 0)) as stock_awal')
            ->selectRaw('(product_supplier.harga_beli * (product_supplier.stock + COALESCE(SUM(transaction_details.quantity), 0))) as total_modal')
            ->groupBy([
                'product_supplier.id',
                'product_supplier.pemodal_user_id',
                'pemodal_users.name',
                'products.name',
                'suppliers.nama_supplier',
                'categories.name',
                'products.category_id',
                'product_supplier.harga_beli',
                'product_supplier.stock',
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

        if ($request->filled('owner_id')) {
            $reportQuery->where('product_supplier.pemodal_user_id', $request->integer('owner_id'));
        }

        $summary = DB::query()
            ->fromSub(clone $reportQuery, 'report_rows')
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('COUNT(DISTINCT pemodal_user_id) as total_pemodal')
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

        $productSupplierIds = $reportRows->getCollection()
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

        $reportRows->setCollection(
            $reportRows->getCollection()->map(function ($row) use ($sellerBreakdownByStock) {
                $row->seller_breakdown = $sellerBreakdownByStock->get($row->product_supplier_id, collect());
                $row->seller_names = $row->seller_breakdown->pluck('name')->implode(', ');

                return $row;
            })
        );

        return [
            'reportRows' => $reportRows,
            'summary' => [
                'total_rows' => (int) ($summary->total_rows ?? 0),
                'total_pemodal' => (int) ($summary->total_pemodal ?? 0),
                'total_stock_awal' => (int) ($summary->total_stock_awal ?? 0),
                'total_terjual' => (int) ($summary->total_terjual ?? 0),
                'total_stock_ready' => (int) ($summary->total_stock_ready ?? 0),
                'total_modal' => (float) ($summary->total_modal ?? 0),
            ],
            'categories' => $categories,
            'owners' => $owners,
        ];
    }
}
