<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->filled('customer_id') && $this->filled('product_id')) {
            return; // Legacy form logic
        }

        $cart = collect($this->input('cart', []))->map(function ($item) {
            return [
                'product_id' => $item['product_id'] ?? $item['id'] ?? null,
                'name' => $item['name'] ?? null,
                'supplier_id' => $item['supplier_id'] ?? null,
                'product_supplier_id' => $item['product_supplier_id'] ?? $item['pivot_id'] ?? null,
                'qty' => $item['qty'] ?? $item['quantity'] ?? null,
                'price' => $item['price'] ?? null,
                'is_conflict' => $item['is_conflict'] ?? $item['isConflict'] ?? false,
            ];
        })->values()->all();

        $this->merge([
            'transaction_data' => $this->input('transaction_data', $this->input('transactionData', [])),
            'additional_fees' => [
                'installation' => (float) $this->input('additional_fees.installation', 0),
                'service_labor' => (float) $this->input('additional_fees.service_labor', 0),
                'discount' => (float) $this->input('additional_fees.discount', 0),
                'shipping' => 0,
                'marketing' => 0,
            ],
            'service_fee' => (float) $this->input('service_fee', $this->input('serviceFee', $this->sumAdditionalFees())),
            'cart' => $cart,
        ]);
    }

    private function sumAdditionalFees(): float
    {
        return (float) $this->input('additional_fees.installation', 0)
            + (float) $this->input('additional_fees.service_labor', 0);
    }

    public function rules(): array
    {
        if ($this->filled('customer_id') && $this->filled('product_id')) {
            return [
                'customer_id' => 'required|exists:customers,id',
                'customer_address' => 'nullable|string|max:500',
                'product_id' => 'required|exists:products,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'product_supplier_id' => 'nullable|exists:product_supplier,id',
                'quantity' => 'required|integer|min:1',
                'service_fee' => 'nullable|numeric|min:0',
                'type' => 'required|in:Invoice,Quotation,DO',
                'transaction_date' => 'required|date',
            ];
        }

        return [
            'transaction_data.sales' => 'required|string|max:100',
            'transaction_data.customerName' => 'required|string|max:100',
            'transaction_data.customerPhone' => 'nullable|string|max:20',
            'transaction_data.customerAddress' => 'nullable|string|max:500',
            'transaction_data.type' => 'nullable|string|in:Invoice,Quotation,DO',
            'transaction_data.transactionMode' => 'required|string|in:sparepart,rakit_pc',
            'transaction_data.buildName' => 'nullable|string|max:120',
            'transaction_data.paymentMethod' => 'nullable|string|in:midtrans,cash',
            'service_fee' => 'required|numeric|min:0',
            'additional_fees.installation' => 'nullable|numeric|min:0',
            'additional_fees.service_labor' => 'nullable|numeric|min:0',
            'additional_fees.discount' => 'nullable|numeric|min:0|max:100',
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|exists:products,id',
            'cart.*.supplier_id' => 'required|exists:suppliers,id',
            'cart.*.product_supplier_id' => 'nullable|integer|exists:product_supplier,id',
            'cart.*.qty' => 'required|integer|min:1',
            'cart.*.price' => 'required|numeric|min:0',
            'cart.*.is_conflict' => 'nullable|boolean',
        ];
    }

    public function isLegacyForm(): bool
    {
        return $this->filled('customer_id') && $this->filled('product_id');
    }
}
