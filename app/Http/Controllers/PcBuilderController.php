<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PcBuilderController extends Controller
{
    private function getCategoryNames(string $type): array
    {
        foreach (config('product_specs.categories', []) as $catConfig) {
            $labels = $catConfig['labels'] ?? [];
            if (in_array($type, $labels, true)) {
                return $labels;
            }
        }
        return [$type];
    }

    public function index()
    {
        return view('pc-builder.index');
    }

    public function getCompatible(Request $request)
    {
        $request->validate([
            'type'        => 'required|string|max:100',
            'socket_type' => 'nullable|string|max:100',
            'ram_type'    => 'nullable|string|max:100',
            'min_wattage' => 'nullable|integer|min:0',
        ]);

        $type       = $request->string('type')->toString();
        $socketType = $request->string('socket_type')->toString() ?: null;
        $ramType    = $request->string('ram_type')->toString() ?: null;
        $minWattage = $request->integer('min_wattage') ?: null;

        $categoryNames = $this->getCategoryNames($type);

        $query = Product::query()
            ->whereHas('category', fn($q) => $q->whereIn('name', $categoryNames))
            ->with(['specs', 'suppliers']);

        // =====================================================================
        // FILTER SOCKET TYPE — Processor & CPU Cooler
        // specs() sekarang ke spec_value_presets via pivot,
        // tapi whereHas tetap bisa query spec_key / spec_value di tabel preset.
        // =====================================================================
        if ($socketType && in_array($type, ['Processor', 'CPU Cooler'], true)) {
            $query->whereHas('specs', fn($q) =>
                $q->where('spec_key', 'socket_type')
                  ->where('spec_value', $socketType)
            );
        }

        // =====================================================================
        // FILTER RAM TYPE
        // BUG LAMA: pakai $socketType — seharusnya $ramType ✓
        // =====================================================================
        if ($ramType && $type === 'RAM') {
            $query->whereHas('specs', fn($q) =>
                $q->where('spec_key', 'ram_type')
                  ->where('spec_value', $ramType) // ← fix: $ramType bukan $socketType
            );
        }

        // =====================================================================
        // FILTER PSU WATTAGE
        // BUG LAMA: pakai ->where('spec_value', $socketType) — harusnya
        // perbandingan numerik CAST(spec_value AS UNSIGNED) >= $minWattage ✓
        // =====================================================================
        if ($minWattage && $type === 'Power Supply') {
            $query->whereHas('specs', fn($q) =>
                $q->where('spec_key', 'total_wattage')
                  ->whereRaw('CAST(spec_value AS UNSIGNED) >= ?', [$minWattage]) // ← fix
            );
        }

        $query->whereHas('suppliers', fn($q) =>
            $q->where('product_supplier.stock', '>', 0)
        );

        return response()->json(
            $query->get()->map(fn($p) => $this->formatProduct($p))
        );
    }

    private function formatProduct($product): array
    {
        // specs() sekarang return koleksi SpecValuePreset (punya spec_key & spec_value)
        // pluck() tetap bekerja persis sama
        $specs = $product->specs
            ->pluck('spec_value', 'spec_key')
            ->toArray();

        $hargaJual = $product->suppliers
            ->filter(fn($s) => $s->pivot->stock > 0)
            ->min(fn($s) => $s->pivot->harga_jual_manual) ?? 0;

        return [
            'id'        => $product->id,
            'name'      => $product->name,
            'brand'     => $product->brand ?? '',
            'price'     => (int) $hargaJual,
            'price_fmt' => 'Rp ' . number_format((int) $hargaJual, 0, ',', '.'),
            'specs'     => $specs,
            'socket_type'   => $specs['socket_type']   ?? null,
            'ram_type_slot' => $specs['ram_type_slot'] ?? null,
            'tdp_watt'      => isset($specs['tdp_watt'])     ? (int) $specs['tdp_watt']     : null,
            'ram_type'      => $specs['ram_type']      ?? null,
            'min_psu_watt'  => isset($specs['min_psu_watt']) ? (int) $specs['min_psu_watt'] : null,
            'total_wattage' => isset($specs['total_wattage'])? (int) $specs['total_wattage']: null,
        ];
    }
}
