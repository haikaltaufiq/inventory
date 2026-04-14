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
            'transaction_data.customerAddress' => 'nullable|string|max:500',
            'transaction_data.type' => 'nullable|string|in:Invoice,Quotation,DO',
            'transaction_data.transactionMode' => 'required|string|in:sparepart,rakit_pc',
            'transaction_data.buildName' => 'nullable|string|max:120',
            'service_fee' => 'required|numeric|min:0',
            'additional_fees.installation' => 'nullable|numeric|min:0',
            'additional_fees.service_labor' => 'nullable|numeric|min:0',
            'additional_fees.shipping' => 'nullable|numeric|min:0',
            'additional_fees.marketing' => 'nullable|numeric|min:0',
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
                $mode = $validated['transaction_data']['transactionMode'] ?? 'sparepart';
                $pcBuildName = trim((string) ($validated['transaction_data']['buildName'] ?? ''));
                if ($mode === 'rakit_pc' && $pcBuildName === '') {
                    throw ValidationException::withMessages([
                        'transaction_data.buildName' => ['Nama barang untuk transaksi Rakit PC wajib diisi.'],
                    ]);
                }

                $installationFee = (float) data_get($validated, 'additional_fees.installation', 0);
                $serviceLaborFee = (float) data_get($validated, 'additional_fees.service_labor', 0);
                $shippingFee = (float) data_get($validated, 'additional_fees.shipping', 0);
                $marketingFee = (float) data_get($validated, 'additional_fees.marketing', 0);
                $serviceFee = (float) $validated['service_fee'];
                $finalTotal = $subtotal + $serviceFee;
                $pcSpecification = $mode === 'rakit_pc'
                    ? $this->buildPcSpecification($cart->all())
                    : null;

                $transaction = Transaction::create([
                    'customer_id' => $customer->id,
                    'sales_name' => $validated['transaction_data']['sales'],
                    'transaction_mode' => $mode,
                    'subtotal' => $subtotal,
                    'service_fee' => $serviceFee,
                    'installation_fee' => $installationFee,
                    'service_labor_fee' => $serviceLaborFee,
                    'shipping_fee' => $shippingFee,
                    'marketing_fee' => $marketingFee,
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
                        'item_name' => $mode === 'rakit_pc' ? $pcBuildName : null,
                        'item_specification' => $mode === 'rakit_pc' ? $pcSpecification : null,
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
            ->selectRaw('COALESCE(SUM(gross_profit_total), 0) as total_profit')
            ->first();

        $reportRows = (clone $reportQuery)
            ->orderByDesc('transaction_date')
            ->orderByDesc('transaction_id')
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
            fputcsv($handle, ['Nama Seller', 'Date', 'Tipe Transaksi', 'Nama Barang', 'Spesifikasi', 'Nama Customer', 'Modal', 'Harga Jual', 'Biaya Tambahan', 'Profit Kotor', 'Penjual 70%', 'NATOPC 30%', 'Status']);

            $this->buildTransactionReportQuery($request)
                ->orderByDesc('transaction_date')
                ->orderByDesc('transaction_id')
                ->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        $productName = trim((string) ($row->product_name ?? '')) !== '' ? $row->product_name : '-';

                        fputcsv($handle, [
                            $row->seller_name ?: '-',
                            $row->transaction_date ? date('d, M, Y', strtotime((string) $row->transaction_date)) : '-',
                            $row->transaction_mode === 'rakit_pc' ? 'Rakit PC' : 'Sparepart only',
                            $productName,
                            $row->item_specification ?: '-',
                            $row->customer_name ?: '-',
                            (float) $row->modal_total,
                            (float) $row->selling_total,
                            (float) $row->service_total,
                            (float) $row->gross_profit_total,
                            (float) $row->seller_profit_share,
                            (float) $row->natopc_profit_share,
                            $row->status ?: '-',
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
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
            ]);
    }

    /**
     * Satu baris per transaction_detail (untuk diagregasi per transaksi di laporan).
     */
    private function buildTransactionReportLineSubquery(Request $request)
    {
        $query = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->leftJoin('customers as c', 't.customer_id', '=', 'c.id')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->leftJoin('product_supplier as ps', 'td.product_supplier_id', '=', 'ps.id')
            ->leftJoin('product_specifications as pspec', 'p.id', '=', 'pspec.product_id')
            ->select([
                'td.id as transaction_detail_id',
                'td.transaction_id',
                't.sales_name as seller_name',
                't.transaction_mode',
                't.transaction_date',
                't.status',
                'c.name as customer_name',
                'c.phone as customer_phone',
                'c.address as customer_address',
                'td.item_name',
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
            ])
            ->selectRaw('(td.quantity * COALESCE(ps.harga_beli, 0)) as modal_line')
            ->selectRaw('(td.quantity * COALESCE(td.price_at_transaction, 0)) as selling_line')
            ->selectRaw('CASE
                WHEN COALESCE(t.subtotal, 0) > 0
                    THEN ROUND(COALESCE(t.service_fee, 0) * ((td.quantity * COALESCE(td.price_at_transaction, 0)) / t.subtotal), 2)
                ELSE 0
            END as service_line')
            ->selectRaw('((td.quantity * COALESCE(td.price_at_transaction, 0)) - (td.quantity * COALESCE(ps.harga_beli, 0))) as gross_line')
            ->selectRaw('ROUND((((td.quantity * COALESCE(td.price_at_transaction, 0)) - (td.quantity * COALESCE(ps.harga_beli, 0))) * 0.7), 2) as seller_line')
            ->selectRaw('ROUND((((td.quantity * COALESCE(td.price_at_transaction, 0)) - (td.quantity * COALESCE(ps.harga_beli, 0))) * 0.3), 2) as natopc_line')
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
                'td.item_name',
                'td.item_specification',
                'p.name',
                'td.quantity',
                'td.price_at_transaction',
                'ps.harga_beli',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $like = '%' . $search . '%';

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
                    'name' => $item['name'] ?? null,
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
            'additional_fees' => [
                'installation' => (float) $request->input('additional_fees.installation', 0),
                'service_labor' => (float) $request->input('additional_fees.service_labor', 0),
                'shipping' => (float) $request->input('additional_fees.shipping', 0),
                'marketing' => (float) $request->input('additional_fees.marketing', 0),
            ],
            'service_fee' => (float) $request->input('service_fee', $request->input('serviceFee', $this->sumAdditionalFees($request))),
            'cart' => $cart,
        ];
    }

    private function sumAdditionalFees(Request $request): float
    {
        return (float) $request->input('additional_fees.installation', 0)
            + (float) $request->input('additional_fees.service_labor', 0)
            + (float) $request->input('additional_fees.shipping', 0)
            + (float) $request->input('additional_fees.marketing', 0);
    }

    private function buildPcSpecification(array $cart): string
    {
        $productIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
        $namesById = $productIds === []
            ? collect()
            : Product::query()->whereIn('id', $productIds)->pluck('name', 'id');

        $specParts = collect($cart)
            ->map(function ($item) use ($namesById) {
                $name = trim((string) data_get($item, 'name', ''));
                if ($name === '') {
                    $pid = data_get($item, 'product_id');
                    $name = $pid ? trim((string) ($namesById[$pid] ?? '')) : '';
                }
                $qty = (int) data_get($item, 'qty', 1);
                if ($name === '') {
                    return null;
                }

                return $qty > 1 ? "{$name} x{$qty}" : $name;
            })
            ->filter()
            ->values();

        return $specParts->implode(', ');
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
        $address = trim((string) ($transactionData['customerAddress'] ?? ''));

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
            if ($address !== '') {
                $customer->address = $address;
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
            'address' => $address !== '' ? $address : '-',
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
            'customer_address' => 'nullable|string|max:500',
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
            if (!empty($validated['customer_address'])) {
                Customer::query()
                    ->whereKey($validated['customer_id'])
                    ->update(['address' => $validated['customer_address']]);
            }

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
