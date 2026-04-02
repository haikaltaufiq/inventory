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
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $currentMonthStart->copy()->subMonth()->endOfMonth();

        $monthlyRows = Transaction::query()
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('COALESCE(SUM(final_total), 0) as revenue_total')
            ->where('transaction_date', '>=', $currentMonthStart->copy()->subMonths(5)->toDateString())
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
        $inventoryTotalStock = array_sum($inventorySeries);

        $totalTransactions = Transaction::count();
        $totalRevenue = (float) Transaction::sum('final_total');
        $totalProducts = Product::count();
        $lowStockItems = DB::table('product_supplier')->where('stock', '<=', 5)->count();

        $currentMonthTransactions = Transaction::query()
            ->whereBetween('transaction_date', [$currentMonthStart->toDateString(), $currentMonthEnd->toDateString()])
            ->count();
        $previousMonthTransactions = Transaction::query()
            ->whereBetween('transaction_date', [$previousMonthStart->toDateString(), $previousMonthEnd->toDateString()])
            ->count();

        $currentMonthRevenue = (float) Transaction::query()
            ->whereBetween('transaction_date', [$currentMonthStart->toDateString(), $currentMonthEnd->toDateString()])
            ->sum('final_total');
        $previousMonthRevenue = (float) Transaction::query()
            ->whereBetween('transaction_date', [$previousMonthStart->toDateString(), $previousMonthEnd->toDateString()])
            ->sum('final_total');

        $currentMonthProducts = Product::query()
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();
        $previousMonthProducts = Product::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

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
            'total_products' => $totalProducts,
            'total_customers' => Customer::count(),
            'total_transactions' => $totalTransactions,
            'total_revenue' => $totalRevenue,
            'low_stock_items' => $lowStockItems,
            'chart_labels' => $chartLabels,
            'revenue_series' => $revenueSeries,
            'transaction_series' => $transactionSeries,
            'inventory_labels' => $inventoryLabels,
            'inventory_series' => $inventorySeries,
            'inventory_total_stock' => $inventoryTotalStock,
            'current_month_label' => $currentMonthStart->translatedFormat('F Y'),
            'current_month_transactions' => $currentMonthTransactions,
            'current_month_revenue' => $currentMonthRevenue,
            'kpis' => [
                [
                    'label' => 'Total Transactions',
                    'value' => number_format($totalTransactions),
                    'icon' => 'fa-receipt',
                    'accent' => 'blue',
                    'helper' => number_format($currentMonthTransactions) . ' transaksi tercatat bulan ini',
                    'trend' => $this->buildDeltaMeta(
                        $currentMonthTransactions,
                        $previousMonthTransactions,
                        'vs bulan lalu'
                    ),
                ],
                [
                    'label' => 'Total Revenue',
                    'value' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                    'icon' => 'fa-wallet',
                    'accent' => 'emerald',
                    'helper' => 'Rp ' . number_format($currentMonthRevenue, 0, ',', '.') . ' revenue bulan ini',
                    'trend' => $this->buildDeltaMeta(
                        $currentMonthRevenue,
                        $previousMonthRevenue,
                        'vs bulan lalu'
                    ),
                ],
                [
                    'label' => 'Total Products',
                    'value' => number_format($totalProducts),
                    'icon' => 'fa-boxes-stacked',
                    'accent' => 'amber',
                    'helper' => number_format($currentMonthProducts) . ' produk ditambahkan bulan ini',
                    'trend' => $this->buildDeltaMeta(
                        $currentMonthProducts,
                        $previousMonthProducts,
                        'produk baru vs bulan lalu'
                    ),
                ],
                [
                    'label' => 'Low Stock Items',
                    'value' => number_format($lowStockItems),
                    'icon' => 'fa-triangle-exclamation',
                    'accent' => 'rose',
                    'helper' => 'Ambang minimum stok saat ini: 5 unit',
                    'trend' => $this->buildLowStockMeta($lowStockItems),
                ],
            ],
            'recent_transactions' => $recentTransactions,
        ];

        return view('dashboard.index', compact('stats'));
    }

    private function buildDeltaMeta(int|float $currentValue, int|float $previousValue, string $suffix): array
    {
        $delta = $currentValue - $previousValue;
        $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
        $state = $delta > 0 ? 'positive' : ($delta < 0 ? 'negative' : 'neutral');

        if ($previousValue > 0) {
            $percent = abs(($delta / $previousValue) * 100);
        } else {
            $percent = $currentValue > 0 ? 100 : 0;
        }

        if ($direction === 'flat') {
            $label = 'Stabil ' . $suffix;
        } else {
            $label = sprintf(
                '%s%s%% %s',
                $direction === 'up' ? '+' : '-',
                $this->formatPercent($percent),
                $suffix
            );
        }

        return [
            'direction' => $direction,
            'state' => $state,
            'label' => $label,
        ];
    }

    private function buildLowStockMeta(int $lowStockItems): array
    {
        if ($lowStockItems === 0) {
            return [
                'direction' => 'down',
                'state' => 'positive',
                'label' => 'Semua stok aman di atas batas minimum',
            ];
        }

        if ($lowStockItems <= 5) {
            return [
                'direction' => 'flat',
                'state' => 'neutral',
                'label' => 'Area aman, tetap pantau item kritis',
            ];
        }

        return [
            'direction' => 'up',
            'state' => 'negative',
            'label' => 'Perlu restock prioritas secepatnya',
        ];
    }

    private function formatPercent(float $value): string
    {
        $precision = $value >= 10 || floor($value) === $value ? 0 : 1;

        return number_format($value, $precision, ',', '.');
    }
}
