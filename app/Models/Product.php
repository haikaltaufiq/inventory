<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand',
        'name',
        'letak_barang',
        'warranty',
        'description',
        'technical_specs',
        'image_url'
    ];

    protected $casts = [
        'technical_specs' => 'array',
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
        $pivotFields = [
            'condition',
            'stock',
            'harga_beli',
            'harga_jual_manual',
            'warranty_detail',
            'note',
            'entry_date',
        ];

        if (Schema::hasColumn('product_supplier', 'pemodal_user_id')) {
            $pivotFields[] = 'pemodal_user_id';
        }

        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot($pivotFields)
            ->withTimestamps();
    }
}
