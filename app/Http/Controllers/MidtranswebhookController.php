<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    public function handle(Request $request)
    {
        // // test ngrok
        // header("ngrok-skip-browser-warning: true");

        // if ($request->isMethod('get')) {
        //     return response()->json([
        //         'status' => 'ready',
        //         'message' => 'Webhook endpoint is active'
        //     ], 200);
        // }

        $payload = $request->all();

        Log::info('Midtrans webhook received', $payload);

        // Verifikasi signature (Keamanan)
        if (! $this->midtrans->verifyNotification($payload)) {
            Log::warning('Midtrans webhook: invalid signature', $payload);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Ambil ID Transaksi dari format "TRX-{id}-{timestamp}"
        $orderId = $payload['order_id'] ?? '';
        preg_match('/^TRX-(\d+)-/', $orderId, $matches);
        $transactionId = $matches[1] ?? null;

        if (! $transactionId) {
            Log::warning('Midtrans webhook: order_id tidak dikenali', ['order_id' => $orderId]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Cari transaksi di DB
        $transaction = Transaction::find($transactionId);
        if (! $transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Map status & update ke kolom 'status' (sesuai service kamu)
        $newStatus = $this->midtrans->mapPaymentStatus(
            $payload['transaction_status'] ?? '',
            $payload['fraud_status'] ?? ''
        );

        // Pastikan mapping output
        $dbStatus = ($newStatus === 'paid') ? 'Completed' : ucfirst($newStatus);

        $transaction->update(['status' => $dbStatus]);

        Log::info('Midtrans webhook: status updated', [
            'transaction_id' => $transactionId,
            'status'         => $dbStatus,
        ]);

        return response()->json(['message' => 'OK']);
    }

    /**
     * Fungsi pembantu untuk mengembalikan stok jika transaksi gagal/expire
     */
    private function returnStock(Transaction $transaction)
    {
        $transaction->load('details');
        foreach ($transaction->details as $detail) {
            if ($detail->product_supplier_id) {
                DB::table('product_supplier')
                    ->where('id', $detail->product_supplier_id)
                    ->increment('stock', $detail->quantity);

                Log::info("Stock returned for product: {$detail->product_id} qty: {$detail->quantity}");
            }
        }
    }
}
