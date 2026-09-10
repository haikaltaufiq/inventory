<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\CacheVersions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    /**
     * Small, on-demand result set for the POS customer picker.  Do not embed
     * every customer in the transaction page: that makes the HTML response
     * grow linearly with the customer table.
     */
    public function lookup(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $customers = Cache::remember(
            'customers:lookup:v' . CacheVersions::customers() . ':' . md5(mb_strtolower($term)),
            now()->addMinutes(10),
            function () use ($term) {
                return Customer::query()
                    ->select(['id', 'name', 'phone', 'address', 'email'])
                    ->where(function ($query) use ($term) {
                        $like = '%' . $term . '%';
                        $query->where('name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orderBy('name')
                    ->limit(20)
                    ->get();
            }
        );

        return response()->json($customers);
    }

    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(10)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string'
        ]);

        Customer::create($validated);
        Cache::forget('transactions:customers');
        CacheVersions::bumpCustomers();

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil ditambahkan');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string'
        ]);

        $customer->update($validated);
        Cache::forget('transactions:customers');
        CacheVersions::bumpCustomers();

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil diupdate');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        Cache::forget('transactions:customers');
        CacheVersions::bumpCustomers();

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil dihapus');
    }
}
