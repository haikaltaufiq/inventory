<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Support\CacheVersions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function storeTransaction(array $validated): Transaction
    {
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
            $discountPercent = min(100, max(0, (float) data_get($validated, 'additional_fees.discount', 0)));
            $serviceFee = (float) $validated['service_fee'];
            $discountFee = round(($subtotal + $serviceFee) * $discountPercent / 100, 2);

            $finalTotal = $subtotal + $serviceFee - $discountFee;

            $pcSpecification = $mode === 'rakit_pc'
                ? $this->buildPcSpecification($cart->all())
                : null;

            $paymentMethod = $validated['transaction_data']['paymentMethod'] ?? 'midtrans';
            $status = $paymentMethod === 'cash' ? 'Completed' : 'Pending';
            $paymentStatus = $paymentMethod === 'cash' ? 'paid' : 'pending';

            $transaction = Transaction::create([
                'customer_id' => $customer->id,
                'sales_name' => $validated['transaction_data']['sales'],
                'transaction_mode' => $mode,
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'installation_fee' => $installationFee,
                'service_labor_fee' => $serviceLaborFee,
                'shipping_fee' => 0,
                'discount_fee' => $discountFee,
                'marketing_fee' => 0,
                'final_total' => $finalTotal,
                'status' => $status,
                'payment_status' => $paymentStatus,
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

        CacheVersions::bumpCatalog();

        return $transaction;
    }

    public function storeLegacyTransaction(array $validated, ?string $userName): Transaction
    {
        $salesName = trim((string) ($userName ?? 'Guest'));

        $transaction = DB::transaction(function () use ($validated, $salesName) {
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
                'status' => 'Pending',
                'type' => $validated['type'],
                'transaction_date' => $validated['transaction_date'],
            ]);

            // Decrement stock first so any constraint error rolls back atomically.
            DB::table('product_supplier')
                ->where('id', $stockRow->id)
                ->decrement('stock', $validated['quantity']);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $validated['product_id'],
                'supplier_id' => $validated['supplier_id'],
                'product_supplier_id' => $stockRow->id,
                'quantity' => $validated['quantity'],
                'price_at_transaction' => $price,
                'is_conflict' => false,
            ]);

            return $transaction;
        });

        CacheVersions::bumpCatalog();

        return $transaction;
    }

    public function deleteTransaction(Transaction $transaction): void
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

        CacheVersions::bumpCatalog();
    }

    public function updateDescription(Transaction $transaction, ?string $description): void
    {
        $transaction->update([
            'description' => $description
        ]);
    }

    public function updateWarranty(Transaction $transaction, ?string $warranty): void
    {
        $transaction->load('details');

        DB::transaction(function () use ($transaction, $warranty) {
            foreach ($transaction->details as $detail) {
                if ($detail->product_supplier_id) {
                    DB::table('product_supplier')
                        ->where('id', $detail->product_supplier_id)
                        ->update(['warranty_detail' => $warranty]);
                }
            }
        });
    }

    public function mapDocumentTypeKey(string $type): string
    {
        return match ($type) {
            'Invoice' => 'invoice',
            'Quotation' => 'quotation',
            'DO' => 'do',
            default => 'invoice',
        };
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
}
