<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'selling_price',
        'warranty',
        'description',
        'image_url'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Ini buat simpan 'socket', 'ram_type' dll
    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class);
    }

    // Relasi ke Supplier dengan Pivot Data
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot([
                'condition',
                'stock',
                'harga_beli',
                'harga_jual_manual',
                'warranty_detail',
                'note',
                'entry_date'
            ])
            ->withTimestamps();
    }
}
