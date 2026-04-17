<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    // ─────────────────────────────────────────────
    // Terima POST notifikasi dari Midtrans
    // Route: POST /api/midtrans/webhook
    // ─────────────────────────────────────────────
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Midtrans webhook received', $payload);

        // 1. Verifikasi signature
        if (! $this->midtrans->verifyNotification($payload)) {
            Log::warning('Midtrans webhook: invalid signature', $payload);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 2. Ambil transaction ID dari order_id
        // Format order_id kita: "TRX-{id}-{timestamp}"
        $orderId = $payload['order_id'] ?? '';
        preg_match('/^TRX-(\d+)-/', $orderId, $matches);
        $transactionId = $matches[1] ?? null;

        if (! $transactionId) {
            Log::warning('Midtrans webhook: order_id tidak dikenali', ['order_id' => $orderId]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 3. Cari transaksi
        $transaction = Transaction::find($transactionId);

        if (! $transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // 4. Map status & update
        $newStatus = $this->midtrans->mapPaymentStatus(
            $payload['transaction_status'] ?? '',
            $payload['fraud_status'] ?? ''
        );

        $transaction->update(['payment_status' => $newStatus]);

        Log::info('Midtrans webhook: status updated', [
            'transaction_id' => $transactionId,
            'status'         => $newStatus,
        ]);

        return response()->json(['message' => 'OK']);
    }
}
