<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_products' => Product::count(),
            'total_customers' => Customer::count(),
            'total_transactions' => Transaction::count(),
            'total_revenue' => Transaction::sum('total_price'),
            'recent_transactions' => Transaction::with(['customer', 'product'])
                ->latest()
                ->take(5)
                ->get()
        ];

        return view('dashboard.index', compact('stats'));
    }
}
