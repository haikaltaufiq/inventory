<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $monthlyRows = Transaction::query()
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('COALESCE(SUM(final_total), 0) as revenue_total')
            ->where('transaction_date', '>=', now()->startOfMonth()->subMonths(5)->toDateString())
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $monthKeys = collect(range(5, 0))
            ->map(fn($back) => now()->subMonths($back)->format('Y-m'));

        $chartLabels = $monthKeys
            ->map(fn($monthKey) => Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('M'))
            ->values()
            ->all();

        $revenueSeries = $monthKeys
            ->map(fn($monthKey) => (float) ($monthlyRows[$monthKey]->revenue_total ?? 0))
            ->values()
            ->all();

        $transactionSeries = $monthKeys
            ->map(fn($monthKey) => (int) ($monthlyRows[$monthKey]->transaction_count ?? 0))
            ->values()
            ->all();

        $inventoryRows = Category::query()
            ->leftJoin('products', 'products.category_id', '=', 'categories.id')
            ->leftJoin('product_supplier', 'product_supplier.product_id', '=', 'products.id')
            ->select('categories.name')
            ->selectRaw('COALESCE(SUM(product_supplier.stock), 0) as total_stock')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_stock')
            ->get()
            ->filter(fn($row) => (int) $row->total_stock > 0)
            ->take(6)
            ->values();

        $inventoryLabels = $inventoryRows->pluck('name')->all();
        $inventorySeries = $inventoryRows->pluck('total_stock')->map(fn($stock) => (int) $stock)->all();

        if (empty($inventorySeries)) {
            $inventoryLabels = ['No Stock Data'];
            $inventorySeries = [1];
        }

        $recentTransactions = Transaction::query()
            ->with([
                'customer:id,name',
                'details:id,transaction_id,product_id,quantity',
                'details.product:id,name',
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->take(8)
            ->get()
            ->map(function ($transaction) {
                $items = $transaction->details->pluck('product.name')->filter()->values();
                $itemSummary = $items->take(2)->implode(', ');
                if ($items->count() > 2) {
                    $itemSummary .= ' +' . ($items->count() - 2) . ' item';
                }

                return [
                    'invoice' => 'TRX-' . str_pad((string) $transaction->id, 5, '0', STR_PAD_LEFT),
                    'customer' => $transaction->customer?->name ?? 'Walk-in Customer',
                    'date' => optional($transaction->transaction_date)->format('d M Y') ?? '-',
                    'total' => (float) $transaction->final_total,
                    'status' => $transaction->status,
                    'items' => $itemSummary !== '' ? $itemSummary : '-',
                ];
            });

        $stats = [
            'total_products' => Product::count(),
            'total_customers' => Customer::count(),
            'total_transactions' => Transaction::count(),
            'total_revenue' => (float) Transaction::sum('final_total'),
            'low_stock_items' => DB::table('product_supplier')->where('stock', '<=', 5)->count(),
            'chart_labels' => $chartLabels,
            'revenue_series' => $revenueSeries,
            'transaction_series' => $transactionSeries,
            'inventory_labels' => $inventoryLabels,
            'inventory_series' => $inventorySeries,
            'recent_transactions' => $recentTransactions,
        ];

        return view('dashboard.index', compact('stats'));
    }
}
