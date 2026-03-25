<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PcBuilderController extends Controller
{
    public function index()
    {
        return view('pc-builder.index');
    }
}
