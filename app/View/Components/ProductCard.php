<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProductCard extends Component
{
    public string $name;
    public string $category;
    public string $supplier;
    public int|float $price;
    public ?string $image;

    public function __construct(
        string $name,
        string $category,
        string $supplier,
        int|float $price,
        ?string $image = null
    ) {
        $this->name = $name;
        $this->category = $category;
        $this->supplier = $supplier;
        $this->price = $price;
        $this->image = $image;
    }

    public function render(): View|Closure|string
    {
        return view('components.product-card');
    }
}
