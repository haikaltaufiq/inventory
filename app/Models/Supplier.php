<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    protected $fillable = [
        'nama_supplier',
        'alamat'
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot(['condition', 'stock', 'harga_beli', 'harga_jual_manual'])
            ->withTimestamps();
    }
}
