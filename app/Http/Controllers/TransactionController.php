<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'product', 'supplier']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                // search customer
                $q->whereHas('customer', function ($qc) use ($search) {
                    $qc->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                    // search product
                    ->orWhereHas('product', function ($qp) use ($search) {
                        $qp->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    })
                    // search supplier
                    ->orWhereHas('supplier', function ($qs) use ($search) {
                        $qs->where('nama_supplier', 'like', "%{$search}%");
                    })
                    // search transaksi sendiri
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('transaction_date', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::with('suppliers')->get();

        return view('transactions.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:Invoice,Quotation,DO',
            'transaction_date' => 'required|date'
        ]);

        DB::beginTransaction();
        try {
            // Cek relasi product-supplier
            $productSupplier = DB::table('product_supplier')
                ->where('product_id', $validated['product_id'])
                ->where('supplier_id', $validated['supplier_id'])
                ->first();

            if (!$productSupplier) {
                return back()->with('error', 'Supplier tidak menjual produk ini');
            }

            // Cek stock
            if ($productSupplier->stock < $validated['quantity']) {
                return back()->with('error', 'Stok supplier tidak mencukupi. Stok tersedia: ' . $productSupplier->stock);
            }

            // Hitung total menggunakan harga jual dari supplier
            $validated['total_price'] = $productSupplier->harga_jual * $validated['quantity'];
            $validated['status'] = 'Pending'; // Default status

            // Create transaction
            Transaction::create($validated);

            // Kurangi stock di pivot table
            DB::table('product_supplier')
                ->where('product_id', $validated['product_id'])
                ->where('supplier_id', $validated['supplier_id'])
                ->decrement('stock', $validated['quantity']);

            DB::commit();

            return redirect()->route('transactions.index')
                ->with('success', 'Transaksi berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(Transaction $transaction)
    {
        DB::beginTransaction();
        try {
            // Kembalikan stock ke supplier
            DB::table('product_supplier')
                ->where('product_id', $transaction->product_id)
                ->where('supplier_id', $transaction->supplier_id)
                ->increment('stock', $transaction->quantity);

            $transaction->delete();

            DB::commit();

            return redirect()->route('transactions.index')
                ->with('success', 'Transaksi berhasil dihapus dan stok dikembalikan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function report()
    {
        $transactions = Transaction::with(['customer', 'product', 'supplier'])->get();
        $totalRevenue = $transactions->sum('total_price');

        return view('laporan-transaksi.index', compact('transactions', 'totalRevenue'));
    }

    public function downloadReport()
    {
        $transactions = Transaction::with(['customer', 'product', 'supplier'])->get();

        $csvData = "Tanggal,Customer,Produk,Supplier,Jumlah,Total,Tipe,Status\n";

        foreach ($transactions as $trans) {
            $csvData .= sprintf(
                "%s,%s,%s,%s,%d,%s,%s,%s\n",
                $trans->transaction_date->format('Y-m-d'),
                $trans->customer->name,
                $trans->product->name,
                $trans->supplier->nama_supplier ?? '-',
                $trans->quantity,
                $trans->total_price,
                $trans->type,
                $trans->status
            );
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="laporan-transaksi.csv"');
    }

    // API endpoint untuk get supplier by product
    public function getSuppliersByProduct($productId)
    {
        $product = Product::with('suppliers')->findOrFail($productId);

        $suppliers = $product->suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'nama_supplier' => $supplier->nama_supplier,
                'stock' => $supplier->pivot->stock,
                'harga_beli' => $supplier->pivot->harga_beli,
                'harga_jual' => $supplier->pivot->harga_jual,
            ];
        });

        return response()->json($suppliers);
    }
}
