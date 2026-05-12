<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcBuild extends Model
{
    protected $fillable = ['name', 'notes', 'components', 'total_price', 'created_by', 'status', 'total_price', 'harga_jual', 'margin_pct'];
    protected $casts = ['components' => 'array'];

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMarginPctAttribute($value)
    {
        return floatval($value);
    }
}
