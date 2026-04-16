<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'customer_id',
        'sales_name',
        'transaction_mode',
        'subtotal',
        'service_fee',
        'installation_fee',
        'service_labor_fee',
        'shipping_fee',
        'marketing_fee',
        'final_total',
        'type',
        'status',
        'transaction_date',
        'description'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'subtotal' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'installation_fee' => 'decimal:2',
        'service_labor_fee' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'marketing_fee' => 'decimal:2',
        'final_total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
