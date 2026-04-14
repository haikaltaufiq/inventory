<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecValuePreset extends Model
{
    protected $fillable = [
        'spec_key',
        'spec_value',
    ];
}
