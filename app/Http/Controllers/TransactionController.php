<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\MidtransService;
use App\Services\TransactionReportService;
use App\Services\TransactionService;
use App\Support\SchemaCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionRepository $repository,
        private TransactionService $service,
        private TransactionReportService $reportService,
        private MidtransService $midtrans
    ) {}

    public function index()
    {
        $salesUsers = $this->repository->getSalesUsers();
        $categories = $this->repository->getCategories();
        $customers = Customer::select('id','name','phone','address')->get();

        return view('transactions.index', compact('categories', 'salesUsers', 'customers'));
    }

    public function products(Request $request)
    {
        $products = $this->repository->getProductsForIndexPage($request);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'has_more' => $products->hasMorePages(),
            ],
        ]);
    }

    public function create()
    {
        $customers = $this->repository->getCustomersForCreate();
        $products = $this->repository->getProductsForCreate();

        return view('transactions.create', compact('customers', 'products'));
    }

    public function getSuppliersByProduct(Product $product)
    {
        return response()->json($this->repository->getSuppliersByProduct($product));
    }

    public function store(TransactionRequest $request)
    {
        try {
            if ($request->isLegacyForm()) {
                $this->service->storeLegacyTransaction($request->validated(), $request->input('sales_name'));
                return redirect()
                    ->route('transactions.index')
                    ->with('success', 'Transaksi berhasil disimpan.');
            }

            if ($request->input('transaction_data.type') !== 'Invoice') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quotation dan Delivery Order hanya boleh diexport PDF tanpa menyimpan transaksi.',
                ], 422);
            }

            $transaction = $this->service->storeTransaction($request->validated());

            // Create the Midtrans Snap token OUTSIDE the DB transaction so that
            // a Midtrans API failure does not roll back the already-saved record.
            $paymentMethod = $request->input('transaction_data.paymentMethod', 'midtrans');
            if ($paymentMethod === 'midtrans') {
                try {
                    $this->midtrans->createSnapToken($transaction);
                } catch (\Throwable $e) {
                    Log::warning('Snap token creation failed after transaction save', [
                        'transaction_id' => $transaction->id,
                        'message'        => $e->getMessage(),
                    ]);
                    // Non-fatal: transaction is already stored; token can be re-requested later.
                }
            }

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
                    'message' => 'Gagal menyimpan transaksi: ' . $exception->getMessage(),
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['transaction' => 'Gagal menyimpan transaksi: ' . $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            $documentTypeKey = $this->service->mapDocumentTypeKey($transaction->type);
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
                'snap_token' => $transaction->snap_token,
                'payment_method' => $request->input('transaction_data.paymentMethod', 'midtrans'),
            ], 201);
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    public function destroy(Transaction $transaction)
    {
        $this->service->deleteTransaction($transaction);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    public function report(Request $request)
    {
        $data = $this->reportService->getReportData($request);

        return view('laporan-transaksi.index', $data);
    }

    public function downloadReport(Request $request)
    {
        return $this->reportService->downloadReport($request);
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

        $isPcBuilder = $transaction->transaction_mode === 'rakit_pc';

        $pdf = Pdf::loadView('transactions.document', [
            'transaction' => $transaction,
            'document_type' => $typeLabel,
            'document_code' => strtoupper($typeKey === 'delivery-order' ? 'DO' : $typeKey),
            'issued_at' => now(),
            'isPcBuilder' => $isPcBuilder,
        ])->setPaper('a4');

        $fileName = strtolower($typeLabel) . '-' . $transaction->id . '.pdf';

        return $pdf->download($fileName);
    }

    public function exportDraftDocument(TransactionRequest $request)
    {
        $validated = $request->validated();
        $type = data_get($validated, 'transaction_data.type');

        if (!in_array($type, ['Quotation', 'DO'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export draft hanya tersedia untuk Quotation dan Delivery Order.',
            ], 422);
        }

        $transaction = $this->makeDraftTransactionForDocument($validated);
        $typeLabel = $type === 'DO' ? 'Delivery Order' : 'Quotation';
        $documentCode = $type === 'DO' ? 'DO' : 'QUOTATION';
        $isPcBuilder = $transaction->transaction_mode === 'rakit_pc';

        $pdf = Pdf::loadView('transactions.document', [
            'transaction' => $transaction,
            'document_type' => $typeLabel,
            'document_code' => $documentCode,
            'issued_at' => now(),
            'isPcBuilder' => $isPcBuilder,
        ])->setPaper('a4');

        $fileName = strtolower(str_replace(' ', '-', $typeLabel)) . '-' . now()->format('YmdHis') . '.pdf';

        return $pdf->download($fileName);
    }

    private function makeDraftTransactionForDocument(array $validated): Transaction
    {
        $cart = collect($validated['cart']);
        $subtotal = $cart->sum(fn ($item) => $item['price'] * $item['qty']);
        $serviceFee = (float) $validated['service_fee'];
        $installationFee = (float) data_get($validated, 'additional_fees.installation', 0);
        $serviceLaborFee = (float) data_get($validated, 'additional_fees.service_labor', 0);
        $discountPercent = min(100, max(0, (float) data_get($validated, 'additional_fees.discount', 0)));
        $discountFee = round(($subtotal + $serviceFee) * $discountPercent / 100, 2);
        $type = data_get($validated, 'transaction_data.type', 'Quotation');
        $mode = data_get($validated, 'transaction_data.transactionMode', 'sparepart');

        $transaction = new Transaction([
            'sales_name' => data_get($validated, 'transaction_data.sales'),
            'transaction_mode' => $mode,
            'subtotal' => $subtotal,
            'service_fee' => $serviceFee,
            'installation_fee' => $installationFee,
            'service_labor_fee' => $serviceLaborFee,
            'shipping_fee' => 0,
            'discount_fee' => $discountFee,
            'marketing_fee' => 0,
            'final_total' => max(0, $subtotal + $serviceFee - $discountFee),
            'status' => 'Draft',
            'type' => $type === 'DO' ? 'Delivery Order' : $type,
            'transaction_date' => now()->toDateString(),
        ]);
        $transaction->id = now()->format('YmdHis');

        $customer = new Customer([
            'name' => data_get($validated, 'transaction_data.customerName', 'Customer'),
            'phone' => data_get($validated, 'transaction_data.customerPhone', '-'),
            'address' => data_get($validated, 'transaction_data.customerAddress', '-'),
            'email' => '-',
        ]);
        $transaction->setRelation('customer', $customer);

        $productNames = Product::query()
            ->whereIn('id', $cart->pluck('product_id')->all())
            ->pluck('name', 'id');

        $details = $cart->map(function ($item) use ($productNames) {
            $product = new Product([
                'name' => $item['name'] ?? ($productNames[$item['product_id']] ?? 'Item'),
            ]);

            $detail = new \App\Models\TransactionDetail([
                'product_id' => $item['product_id'],
                'supplier_id' => $item['supplier_id'],
                'product_supplier_id' => $item['product_supplier_id'] ?? null,
                'quantity' => $item['qty'],
                'price_at_transaction' => $item['price'],
                'is_conflict' => (bool) ($item['is_conflict'] ?? false),
            ]);
            $detail->setRelation('product', $product);

            return $detail;
        });

        $transaction->setRelation('details', $details);

        return $transaction;
    }

    public function updateDesc(Request $request, Transaction $transaction)
    {
        $request->validate(['description' => 'nullable|string']);

        $this->service->updateDescription($transaction, $request->description);

        return back()->with('success', 'Catatan transaksi (Desc) berhasil diperbarui.');
    }

    public function updateWarranty(Request $request, Transaction $transaction)
    {
        $request->validate(['warranty' => 'nullable|string']);

        $this->service->updateWarranty($transaction, $request->warranty);

        return back()->with('success', 'Garansi (db supplier) berhasil diperbarui untuk transaksi ini.');
    }

    // 2. Tambah method baru getSnapToken():
    // ─────────────────────────────────────────────────────────────────

    public function getSnapToken(Transaction $transaction)
    {
        // Pastikan hanya transaksi yang belum dibayar
        if ($transaction->status === 'Completed' || $transaction->payment_status === 'paid') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi ini sudah lunas.',
            ], 422);
        }

        if ($transaction->snap_token) {
            return response()->json([
                'status'     => 'success',
                'snap_token' => $transaction->snap_token,
                'client_key' => $this->midtrans->getClientKey(),
            ]);
        }

        try {
            $result = $this->midtrans->createSnapToken($transaction);

            return response()->json([
                'status'     => 'success',
                'snap_token' => $result['snap_token'],
                'client_key' => $result['client_key'],
            ]);

        } catch (\Throwable $e) {
            Log::error('getSnapToken failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat token pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    // 3. Tambah method checkPaymentStatus():
    // ─────────────────────────────────────────────────────────────────

    public function checkPaymentStatus(Transaction $transaction)
    {
        $paymentStatus = $transaction->payment_status
            ?: ($transaction->status === 'Completed' ? 'paid' : 'pending');

        return response()->json([
            'status'         => 'success',
            'transaction_status' => $transaction->status,
            'payment_status' => $paymentStatus,
        ]);
    }

    public function markPaid(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'Completed') {
            $payload = ['status' => 'Completed'];

            if (SchemaCache::hasColumn('transactions', 'payment_status')) {
                $payload['payment_status'] = 'paid';
            }

            $transaction->update($payload);
        }

        return response()->json([
            'status' => 'success',
            'transaction_status' => $transaction->fresh()->status,
        ]);
    }
}
