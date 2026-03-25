<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PieChart extends Component
{
    public string $id;
    public array $series;
    public array $labels;

    public function __construct(
        string $id,
        array $series,
        array $labels
    ) {
        $this->id = $id;
        $this->series = $series;
        $this->labels = $labels;
    }

    public function render()
    {
        return view('components.pie-chart');
    }
}
