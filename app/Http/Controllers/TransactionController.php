<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    public function index()
    {
        $salesUsers = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.image_url',
                'products.description',
            ])
            ->with([
                'category:id,name',
                'specifications:id,product_id,spec_key,spec_value',
                'suppliers' => function ($query) {
                    $query
                        ->select('suppliers.id', 'suppliers.nama_supplier')
                        ->where('product_supplier.stock', '>', 0)
                        ->withPivot('id', 'stock', 'harga_jual_manual', 'condition');
                },
            ])
            ->get()
            ->map(function ($product) {
                $specs = $product->specifications->pluck('spec_value', 'spec_key');
                $specItems = $product->specifications
                    ->map(function ($spec) {
                        return [
                            'key' => $spec->spec_key,
                            'value' => $spec->spec_value,
                        ];
                    })
                    ->values()
                    ->all();

                $suppliers = $product->suppliers
                    ->map(function ($supplier) {
                        return [
                            'id' => $supplier->id,
                            'supplier_id' => $supplier->id,
                            'nama_supplier' => $supplier->nama_supplier,
                            'pivot' => [
                                'id' => $supplier->pivot->id,
                                'stock' => (int) $supplier->pivot->stock,
                                'harga_jual_manual' => (float) $supplier->pivot->harga_jual_manual,
                                'condition' => $supplier->pivot->condition,
                            ],
                        ];
                    })
                    ->values();

                $basePrice = $suppliers
                    ->pluck('pivot.harga_jual_manual')
                    ->filter(fn($price) => $price !== null)
                    ->min();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => ['name' => $product->category?->name ?? 'Uncategorized'],
                    'base_price' => (float) ($basePrice ?? 0),
                    'socket' => $specs->get('socket'),
                    'ram_type' => $specs->get('ram_type'),
                    'image_url' => $product->image_url ?? asset('assets/no-image.svg'),
                    'description' => $product->description,
                    'specs' => $specItems,
                    'suppliers' => $suppliers,
                ];
            })
            ->values();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('transactions.index', compact('products', 'categories', 'salesUsers'));
    }

    public function create()
    {
        $customers = Customer::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->select('id', 'name')
            ->withSum('suppliers as total_stock', 'product_supplier.stock')
            ->orderBy('name')
            ->get();

        return view('transactions.create', compact('customers', 'products'));
    }

    public function getSuppliersByProduct(Product $product)
    {
        $product->load([
            'suppliers' => function ($query) {
                $query
                    ->select('suppliers.id', 'suppliers.nama_supplier')
                    ->where('product_supplier.stock', '>', 0)
                    ->withPivot('id', 'condition', 'stock', 'harga_jual_manual');
            },
        ]);

        $suppliers = $product->suppliers
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'supplier_id' => $supplier->id,
                    'product_supplier_id' => $supplier->pivot->id,
                    'nama_supplier' => $supplier->nama_supplier,
                    'condition' => $supplier->pivot->condition,
                    'stock' => (int) $supplier->pivot->stock,
                    'harga_jual' => (float) $supplier->pivot->harga_jual_manual,
                ];
            })
            ->values();

        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        if ($request->filled('customer_id') && $request->filled('product_id')) {
            return $this->storeFromLegacyForm($request);
        }

        $payload = $this->normalizePayload($request);

        $validated = Validator::make($payload, [
            'transaction_data.sales' => 'required|string|max:100',
            'transaction_data.customerName' => 'required|string|max:100',
            'transaction_data.customerPhone' => 'nullable|string|max:20',
            'transaction_data.type' => 'nullable|string|in:Invoice,Quotation,DO',
            'service_fee' => 'required|numeric|min:0',
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|exists:products,id',
            'cart.*.supplier_id' => 'required|exists:suppliers,id',
            'cart.*.product_supplier_id' => 'nullable|integer|exists:product_supplier,id',
            'cart.*.qty' => 'required|integer|min:1',
            'cart.*.price' => 'required|numeric|min:0',
            'cart.*.is_conflict' => 'nullable|boolean',
        ])->validate();

        try {
            $transaction = DB::transaction(function () use ($validated) {
                $customer = $this->resolveCustomer($validated['transaction_data']);
                $cart = collect($validated['cart']);
                $subtotal = $cart->sum(fn($item) => $item['price'] * $item['qty']);
                $serviceFee = (float) $validated['service_fee'];
                $finalTotal = $subtotal + $serviceFee;

                $transaction = Transaction::create([
                    'customer_id' => $customer->id,
                    'sales_name' => $validated['transaction_data']['sales'],
                    'subtotal' => $subtotal,
                    'service_fee' => $serviceFee,
                    'final_total' => $finalTotal,
                    'status' => 'Completed',
                    'type' => $validated['transaction_data']['type'] ?? 'Invoice',
                    'transaction_date' => now()->toDateString(),
                ]);

                foreach ($cart as $item) {
                    $stockQuery = DB::table('product_supplier')
                        ->where('product_id', $item['product_id'])
                        ->where('supplier_id', $item['supplier_id']);

                    if (!empty($item['product_supplier_id'])) {
                        $stockQuery->where('id', $item['product_supplier_id']);
                    }

                    $stockRow = $stockQuery
                        ->where('stock', '>=', $item['qty'])
                        ->lockForUpdate()
                        ->orderByDesc('stock')
                        ->first();

                    if (!$stockRow) {
                        throw ValidationException::withMessages([
                            'cart' => ["Stok tidak cukup untuk produk ID {$item['product_id']}."],
                        ]);
                    }

                    DB::table('product_supplier')
                        ->where('id', $stockRow->id)
                        ->decrement('stock', $item['qty']);

                    TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'supplier_id' => $item['supplier_id'],
                        'product_supplier_id' => $stockRow->id,
                        'quantity' => $item['qty'],
                        'price_at_transaction' => $item['price'],
                        'is_conflict' => (bool) ($item['is_conflict'] ?? false),
                    ]);
                }

                return $transaction;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Transaction store failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan transaksi. Coba lagi.',
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['transaction' => 'Gagal menyimpan transaksi. Coba lagi.']);
        }

        if ($request->expectsJson()) {
            $documentTypeKey = $this->mapDocumentTypeKey($transaction->type);
            session()->flash('success', 'Transaksi berhasil disimpan.');

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil disimpan.',
                'redirect' => route('transactions.index'),
                'document_url' => route('transactions.document', [
                    'transaction' => $transaction->id,
                    'type' => $documentTypeKey,
                ]),
                'transaction_id' => $transaction->id,
            ], 201);
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->load('details');

        DB::transaction(function () use ($transaction) {
            foreach ($transaction->details as $detail) {
                $stockRow = DB::table('product_supplier')
                    ->where('product_id', $detail->product_id)
                    ->where('supplier_id', $detail->supplier_id);

                if (!empty($detail->product_supplier_id)) {
                    $stockRow = (clone $stockRow)
                        ->where('id', $detail->product_supplier_id)
                        ->lockForUpdate()
                        ->first();
                } else {
                    $stockRow = null;
                }

                if (!$stockRow) {
                    $stockRow = DB::table('product_supplier')
                        ->where('product_id', $detail->product_id)
                        ->where('supplier_id', $detail->supplier_id)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->first();
                }

                if ($stockRow) {
                    DB::table('product_supplier')
                        ->where('id', $stockRow->id)
                        ->increment('stock', $detail->quantity);
                }
            }

            $transaction->delete();
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    public function report(Request $request)
    {
        $reportQuery = $this->buildTransactionReportQuery($request);

        $summary = DB::query()
            ->fromSub(clone $reportQuery, 'report_rows')
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('COALESCE(SUM(selling_total), 0) as total_selling')
            ->selectRaw('COALESCE(SUM(service_total), 0) as total_service')
            ->selectRaw('COALESCE(SUM(profit_total), 0) as total_profit')
            ->first();

        $reportRows = (clone $reportQuery)
            ->orderByDesc('transaction_date')
            ->orderByDesc('transaction_id')
            ->orderBy('transaction_detail_id')
            ->paginate(15)
            ->withQueryString();

        return view('laporan-transaksi.index', [
            'reportRows' => $reportRows,
            'summary' => [
                'total_rows' => (int) ($summary->total_rows ?? 0),
                'total_selling' => (float) ($summary->total_selling ?? 0),
                'total_service' => (float) ($summary->total_service ?? 0),
                'total_profit' => (float) ($summary->total_profit ?? 0),
            ],
        ]);
    }

    public function downloadReport(Request $request)
    {
        $fileName = 'laporan-transaksi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nama Seller', 'Date', 'Nama Barang', 'Nama Customer', 'Modal', 'Harga Jual', 'Service', 'Profit']);

            $this->buildTransactionReportQuery($request)
                ->orderByDesc('transaction_date')
                ->orderByDesc('transaction_id')
                ->orderBy('transaction_detail_id')
                ->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        $productName = trim((string) ($row->product_name ?? '')) !== ''
                            ? $row->product_name . ' (Qty: ' . (int) $row->quantity . ')'
                            : '-';

                        fputcsv($handle, [
                            $row->seller_name ?: '-',
                            $row->transaction_date ? date('d, M, Y', strtotime((string) $row->transaction_date)) : '-',
                            $productName,
                            $row->customer_name ?: '-',
                            (float) $row->modal_total,
                            (float) $row->selling_total,
                            (float) $row->service_total,
                            (float) $row->profit_total,
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function buildTransactionReportQuery(Request $request)
    {
        $query = DB::table('transaction_details as transaction_details')
            ->join('transactions as transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->leftJoin('customers as customers', 'transactions.customer_id', '=', 'customers.id')
            ->leftJoin('products as products', 'transaction_details.product_id', '=', 'products.id')
            ->leftJoin('product_supplier as product_supplier', 'transaction_details.product_supplier_id', '=', 'product_supplier.id')
            ->select([
                'transaction_details.id as transaction_detail_id',
                'transactions.id as transaction_id',
                'transactions.sales_name as seller_name',
                'transactions.transaction_date',
                'transactions.status',
                'customers.name as customer_name',
                'products.name as product_name',
                'transaction_details.quantity',
                'transaction_details.price_at_transaction as selling_price_unit',
                'product_supplier.harga_beli as modal_price_unit',
            ])
            ->selectRaw('(transaction_details.quantity * COALESCE(product_supplier.harga_beli, 0)) as modal_total')
            ->selectRaw('(transaction_details.quantity * COALESCE(transaction_details.price_at_transaction, 0)) as selling_total')
            ->selectRaw('CASE
                WHEN COALESCE(transactions.subtotal, 0) > 0
                    THEN ROUND(COALESCE(transactions.service_fee, 0) * ((transaction_details.quantity * COALESCE(transaction_details.price_at_transaction, 0)) / transactions.subtotal), 2)
                ELSE 0
            END as service_total')
            ->selectRaw('((transaction_details.quantity * COALESCE(transaction_details.price_at_transaction, 0)) - (transaction_details.quantity * COALESCE(product_supplier.harga_beli, 0))) as profit_total');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('transactions.sales_name', 'like', "%{$search}%")
                    ->orWhere('customers.name', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transactions.transaction_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transactions.transaction_date', '<=', $request->input('date_to'));
        }

        return $query;
    }

    public function downloadDocument(Transaction $transaction, string $type)
    {
        $typeKey = strtolower($type);
        $typeLabel = match ($typeKey) {
            'invoice' => 'Invoice',
            'quotation' => 'Quotation',
            'do', 'delivery-order' => 'Delivery Order',
            default => null,
        };

        if (!$typeLabel) {
            abort(404);
        }

        $transaction->load([
            'customer:id,name,phone,address,email',
            'details:id,transaction_id,product_id,product_supplier_id,quantity,price_at_transaction',
            'details.product:id,name',
        ]);

        $pdf = Pdf::loadView('transactions.document', [
            'transaction' => $transaction,
            'document_type' => $typeLabel,
            'document_code' => strtoupper($typeKey === 'delivery-order' ? 'DO' : $typeKey),
            'issued_at' => now(),
        ])->setPaper('a4');

        $fileName = strtolower($typeLabel) . '-' . $transaction->id . '.pdf';

        return $pdf->download($fileName);
    }

    private function normalizePayload(Request $request): array
    {
        $cart = collect($request->input('cart', []))
            ->map(function ($item) {
                return [
                    'product_id' => $item['product_id'] ?? $item['id'] ?? null,
                    'supplier_id' => $item['supplier_id'] ?? null,
                    'product_supplier_id' => $item['product_supplier_id'] ?? $item['pivot_id'] ?? null,
                    'qty' => $item['qty'] ?? $item['quantity'] ?? null,
                    'price' => $item['price'] ?? null,
                    'is_conflict' => $item['is_conflict'] ?? $item['isConflict'] ?? false,
                ];
            })
            ->values()
            ->all();

        return [
            'transaction_data' => $request->input('transaction_data', $request->input('transactionData', [])),
            'service_fee' => (float) $request->input('service_fee', $request->input('serviceFee', 0)),
            'cart' => $cart,
        ];
    }

    private function mapDocumentTypeKey(string $type): string
    {
        return match ($type) {
            'Invoice' => 'invoice',
            'Quotation' => 'quotation',
            'DO' => 'do',
            default => 'invoice',
        };
    }

    private function resolveCustomer(array $transactionData): Customer
    {
        $name = trim((string) ($transactionData['customerName'] ?? ''));
        $phone = trim((string) ($transactionData['customerPhone'] ?? ''));

        $customerQuery = Customer::query();

        if ($phone !== '') {
            $customerQuery->where('phone', $phone);
        } else {
            $customerQuery->where('name', $name);
        }

        $customer = $customerQuery->first();

        if ($customer) {
            $customer->name = $name !== '' ? $name : $customer->name;
            if ($phone !== '') {
                $customer->phone = $phone;
            }
            $customer->save();

            return $customer;
        }

        $baseName = Str::slug($name !== '' ? $name : 'customer');
        $emailBase = $baseName !== '' ? $baseName : 'customer';
        $email = $this->generateUniqueCustomerEmail($emailBase, $phone);

        return Customer::create([
            'name' => $name !== '' ? $name : 'Customer POS',
            'email' => $email,
            'phone' => $phone !== '' ? $phone : 'N/A-' . now()->format('YmdHis'),
            'address' => '-',
        ]);
    }

    private function generateUniqueCustomerEmail(string $emailBase, string $phone): string
    {
        $suffix = $phone !== '' ? preg_replace('/\D+/', '', $phone) : now()->format('YmdHis');
        $suffix = $suffix !== '' ? $suffix : (string) now()->timestamp;
        $candidate = "{$emailBase}.{$suffix}@pos.local";
        $counter = 1;

        while (Customer::where('email', $candidate)->exists()) {
            $candidate = "{$emailBase}.{$suffix}.{$counter}@pos.local";
            $counter++;
        }

        return $candidate;
    }

    private function storeFromLegacyForm(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'product_supplier_id' => 'nullable|exists:product_supplier,id',
            'quantity' => 'required|integer|min:1',
            'service_fee' => 'nullable|numeric|min:0',
            'type' => 'required|in:Invoice,Quotation,DO',
            'transaction_date' => 'required|date',
        ]);

        $salesName = trim((string) $request->input('sales_name', ''));
        if ($salesName === '') {
            $salesName = $request->user()?->name ?? 'Guest';
        }

        DB::transaction(function () use ($validated, $salesName) {
            $stockQuery = DB::table('product_supplier')
                ->where('product_id', $validated['product_id'])
                ->where('supplier_id', $validated['supplier_id']);

            if (!empty($validated['product_supplier_id'])) {
                $stockQuery->where('id', $validated['product_supplier_id']);
            }

            $stockRow = $stockQuery
                ->where('stock', '>=', $validated['quantity'])
                ->lockForUpdate()
                ->orderByDesc('stock')
                ->first();

            if (!$stockRow) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stok supplier tidak cukup untuk jumlah transaksi ini.'],
                ]);
            }

            $price = (float) $stockRow->harga_jual_manual;
            $subtotal = $price * (int) $validated['quantity'];
            $serviceFee = (float) ($validated['service_fee'] ?? 0);
            $finalTotal = $subtotal + $serviceFee;

            $transaction = Transaction::create([
                'customer_id' => $validated['customer_id'],
                'sales_name' => $salesName,
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'final_total' => $finalTotal,
                'status' => 'Completed',
                'type' => $validated['type'],
                'transaction_date' => $validated['transaction_date'],
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $validated['product_id'],
                'supplier_id' => $validated['supplier_id'],
                'product_supplier_id' => $stockRow->id,
                'quantity' => $validated['quantity'],
                'price_at_transaction' => $price,
                'is_conflict' => false,
            ]);

            DB::table('product_supplier')
                ->where('id', $stockRow->id)
                ->decrement('stock', $validated['quantity']);
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil disimpan.');
    }
}
