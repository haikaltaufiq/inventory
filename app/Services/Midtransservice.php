<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct()
    {
        $this->configure();
    }

    // ─────────────────────────────────────────────
    // Setup config dari DB setting
    // ─────────────────────────────────────────────
    private function configure(): void
    {
        $config = Setting::midtransConfig();

        Config::$serverKey    = $config['server_key'];
        Config::$clientKey    = $config['client_key'];
        Config::$isProduction = $config['is_production'];
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    // ─────────────────────────────────────────────
    // Buat Snap Token untuk transaksi
    // ─────────────────────────────────────────────
    public function createSnapToken(Transaction $transaction): array
    {
        $transaction->load(['customer', 'details.product']);

        $params = [
            'transaction_details' => [
                'order_id'     => 'TRX-' . $transaction->id . '-' . time(),
                'gross_amount' => (int) $transaction->total_price,
            ],
            'customer_details' => [
                'first_name' => $transaction->customer?->name ?? 'Customer',
                'phone'      => $transaction->customer?->phone ?? '',
                'email'      => $transaction->customer?->email ?? 'noreply@example.com',
            ],
            'item_details' => $this->buildItemDetails($transaction),
            'enabled_payments' => [
                'credit_card', 'bca_va', 'bni_va', 'bri_va',
                'mandiri_clickpay', 'gopay', 'qris', 'shopeepay',
                'indomaret', 'alfamart',
            ],
            'callbacks' => [
                'finish' => route('transactions.index'),
            ],
        ];

        try {
            $snapToken  = Snap::getSnapToken($params);
            $snapUrl    = Config::$isProduction
                ? 'https://app.midtrans.com/snap/v2/vtweb/' . $snapToken
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $snapToken;

            // Simpan snap_token & url ke transaksi
            $transaction->update([
                'snap_token'     => $snapToken,
                'payment_url'    => $snapUrl,
                'payment_status' => 'pending',
            ]);

            return [
                'status'     => 'success',
                'snap_token' => $snapToken,
                'snap_url'   => $snapUrl,
                'client_key' => Setting::get('midtrans_client_key'),
            ];

        } catch (\Throwable $e) {
            Log::error('Midtrans createSnapToken failed', [
                'transaction_id' => $transaction->id,
                'message'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // Build item_details dari detail transaksi
    // ─────────────────────────────────────────────
    private function buildItemDetails(Transaction $transaction): array
    {
        $items = [];

        foreach ($transaction->details as $detail) {
            $items[] = [
                'id'       => 'PROD-' . $detail->product_id,
                'price'    => (int) $detail->price_at_transaction,
                'quantity' => (int) $detail->quantity,
                'name'     => substr($detail->product?->name ?? 'Produk', 0, 50),
            ];
        }

        // Tambahkan biaya tambahan jika ada
        $additionalFields = [
            'installation'  => 'Biaya Instalasi',
            'service_labor' => 'Jasa Layanan',
            'shipping'      => 'Ongkos Kirim',
            'marketing'     => 'Biaya Marketing',
        ];

        foreach ($additionalFields as $field => $label) {
            $amount = (int) ($transaction->{$field} ?? 0);
            if ($amount > 0) {
                $items[] = [
                    'id'       => 'FEE-' . strtoupper($field),
                    'price'    => $amount,
                    'quantity' => 1,
                    'name'     => $label,
                ];
            }
        }

        return $items;
    }

    // ─────────────────────────────────────────────
    // Verifikasi notifikasi webhook dari Midtrans
    // ─────────────────────────────────────────────
    public function verifyNotification(array $payload): bool
    {
        $config     = Setting::midtransConfig();
        $serverKey  = $config['server_key'];
        $orderId    = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';

        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return $signatureKey === ($payload['signature_key'] ?? '');
    }

    // ─────────────────────────────────────────────
    // Map status Midtrans → status internal kita
    // ─────────────────────────────────────────────
    public function mapPaymentStatus(string $transactionStatus, string $fraudStatus = ''): string
    {
        return match (true) {
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'paid',
            $transactionStatus === 'settlement'                           => 'paid',
            $transactionStatus === 'pending'                              => 'pending',
            in_array($transactionStatus, ['deny', 'cancel', 'failure'])   => 'failed',
            $transactionStatus === 'expire'                               => 'expired',
            default                                                       => 'pending',
        };
    }

    // ─────────────────────────────────────────────
    // Ambil client key (untuk JS Snap di frontend)
    // ─────────────────────────────────────────────
    public function getClientKey(): string
    {
        return Setting::get('midtrans_client_key', '');
    }

    // ─────────────────────────────────────────────
    // Cek apakah Midtrans sudah dikonfigurasi
    // ─────────────────────────────────────────────
    public function isConfigured(): bool
    {
        $config = Setting::midtransConfig();
        return !empty($config['server_key']) && !empty($config['client_key']);
    }
}
