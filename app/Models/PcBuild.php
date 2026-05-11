<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcBuild extends Model
{
    protected $fillable = ['name', 'notes', 'components', 'total_price', 'created_by', 'status'];
    protected $casts = ['components' => 'array'];

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
