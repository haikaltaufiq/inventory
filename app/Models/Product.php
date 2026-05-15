<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand',
        'name',
        'serial_number',
        'letak_barang',
        'description',
        'image_url',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Specs produk melalui pivot product_spec_value → spec_value_presets.
     * Menggantikan specifications() HasMany yang lama.
     */
    public function specs(): BelongsToMany
    {
        return $this->belongsToMany(
            SpecValuePreset::class,
            'product_spec_value'
        )->withTimestamps();
    }

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
