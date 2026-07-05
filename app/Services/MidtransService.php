<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use App\Support\SchemaCache;
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
    // Setup config dari DB setting (cached)
    // ─────────────────────────────────────────────
    private function configure(): void
    {
        $config = $this->cachedMidtransConfig();

        Config::$serverKey    = $config['server_key'];
        Config::$clientKey    = $config['client_key'];
        Config::$isProduction = $config['is_production'];
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    /**
     * Return Midtrans config from cache so we don't query the DB on every
     * MidtransService instantiation.  Cache is busted by SettingsController
     * whenever the keys are saved.
     */
    private function cachedMidtransConfig(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            'midtrans:config',
            now()->addMinutes(60),
            fn () => Setting::midtransConfig()
        );
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
                'gross_amount' => (int) $transaction->final_total,
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

            $updatePayload = [
                'snap_token' => $snapToken,
                'payment_url' => $snapUrl,
            ];

            if (SchemaCache::hasColumn('transactions', 'payment_status')) {
                $updatePayload['payment_status'] = 'pending';
            }

            $transaction->update($updatePayload);

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
            'installation_fee'  => 'Biaya Instalasi',
            'service_labor_fee' => 'Jasa Layanan',
        ];

        foreach ($additionalFields as $field => $label) {
            $amount = (int) ($transaction->{$field} ?? 0);
            if ($amount > 0) {
                $items[] = [
                    'id'       => 'FEE-' . strtoupper(str_replace('_fee', '', $field)),
                    'price'    => $amount,
                    'quantity' => 1,
                    'name'     => $label,
                ];
            }
        }

        $listedFeeAmount = (int) ($transaction->installation_fee ?? 0)
            + (int) ($transaction->service_labor_fee ?? 0);
        $otherFeeAmount = max(0, (int) ($transaction->service_fee ?? 0) - $listedFeeAmount);

        if ($otherFeeAmount > 0) {
            $items[] = [
                'id' => 'FEE-ADJUSTMENT',
                'price' => $otherFeeAmount,
                'quantity' => 1,
                'name' => 'Penyesuaian Harga',
            ];
        }

        $discountAmount = (int) ($transaction->discount_fee ?? 0);
        if ($discountAmount > 0) {
            $discountBase = (float) ($transaction->subtotal ?? 0) + (float) ($transaction->service_fee ?? 0);
            $discountPercent = $discountBase > 0 ? round(($discountAmount / $discountBase) * 100, 2) : 0;
            $discountLabel = rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.');

            $items[] = [
                'id' => 'DISCOUNT',
                'price' => -$discountAmount,
                'quantity' => 1,
                'name' => substr('Diskon Transaksi ' . $discountLabel . '%', 0, 50),
            ];
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

    
    public function handleNotification(array $payload): void
    {
        if (!$this->verifyNotification($payload)) {
            Log::warning('Midtrans: Invalid Notification Signature', $payload);
            return;
        }

        $orderIdParts = explode('-', $payload['order_id']);
        // Tadi di createSnapToken kamu pakai format: TRX-{id}-{time}
        // Jadi id transaksinya ada di index ke-1
        $transactionId = $orderIdParts[1] ?? null;

        if (!$transactionId) return;

        $transaction = Transaction::find($transactionId);
        if (!$transaction) return;

        $midtransStatus = $payload['transaction_status'];
        $fraudStatus = $payload['fraud_status'] ?? '';

        $newStatus = $this->mapPaymentStatus($midtransStatus, $fraudStatus);

        // Update status di database
        // Sesuaikan mapping 'paid' atau 'pending' dengan kolom 'status' di tabel kamu
        $transaction->update($this->paymentUpdatePayload($newStatus));

        Log::info("Midtrans Status Updated: TRX-{$transactionId} to {$newStatus}");
    }

    public function paymentUpdatePayload(string $paymentStatus): array
    {
        $dbStatus = match ($paymentStatus) {
            'paid' => 'Completed',
            'failed', 'expired' => 'Cancelled',
            default => 'Pending',
        };

        $payload = ['status' => $dbStatus];

        if (SchemaCache::hasColumn('transactions', 'payment_status')) {
            $payload['payment_status'] = $paymentStatus;
        }

        return $payload;
    }
}
