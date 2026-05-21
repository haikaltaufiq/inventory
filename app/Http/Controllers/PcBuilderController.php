<?php

namespace App\Http\Controllers;

use App\Models\PcBuild;
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
        if ($socketType && in_array($type, ['Processor', 'CPU Cooler', 'Motherboard'], true)) {
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

        if ($ramType && $type === 'Motherboard') {
            $query->whereHas('specs', fn($q) =>
                $q->where('spec_key', 'ram_type_slot')
                  ->where('spec_value', $ramType)
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
        $specs = $product->specs
            ->pluck('spec_value', 'spec_key')
            ->toArray();

        $suppliersWithStock = $product->suppliers
            ->filter(fn($s) => $s->pivot->stock > 0);

        // Ambil harga beli terendah (harga modal)
        $hargaBeli = $suppliersWithStock->min(fn($s) => $s->pivot->harga_beli) ?? 0;

        // Harga jual tetap disimpan untuk referensi
        $hargaJual = $suppliersWithStock->min(fn($s) => $s->pivot->harga_jual_manual) ?? 0;

        return [
            'id'             => $product->id,
            'name'           => $product->name,
            'brand'          => $product->brand ?? '',
            'image_url'      => $product->image_url ?? asset('assets/no-image.svg'),

            // ← price sekarang = harga BELI (modal)
            'price'          => (int) $hargaBeli,
            'price_fmt'      => 'Rp ' . number_format((int) $hargaBeli, 0, ',', '.'),

            // ← harga jual tetap ada untuk referensi di cetak
            'harga_jual'     => (int) $hargaJual,
            'harga_jual_fmt' => 'Rp ' . number_format((int) $hargaJual, 0, ',', '.'),

            'specs'          => $specs,
            'socket_type'    => $specs['socket_type']   ?? null,
            'ram_type_slot'  => $specs['ram_type_slot'] ?? null,
            'tdp_watt'       => isset($specs['tdp_watt'])      ? (int) $specs['tdp_watt']      : null,
            'ram_type'       => $specs['ram_type']       ?? null,
            'ram_type_support' => $specs['ram_type_support'] ?? null,
            'min_psu_watt'   => isset($specs['min_psu_watt'])  ? (int) $specs['min_psu_watt']  : null,
            'total_wattage'  => isset($specs['total_wattage']) ? (int) $specs['total_wattage'] : null,
            'form_factor'    => $specs['form_factor'] ?? null,
            'interface_type' => $specs['interface_type'] ?? null,
            'length_mm'      => isset($specs['length_mm']) ? (int) $specs['length_mm'] : null,
            'height_mm'      => isset($specs['height_mm']) ? (int) $specs['height_mm'] : null,
            'supported_motherboard_sizes' => $specs['supported_motherboard_sizes'] ?? null,
            'max_gpu_length_mm' => isset($specs['max_gpu_length_mm']) ? (int) $specs['max_gpu_length_mm'] : null,
            'max_cpu_cooler_height_mm' => isset($specs['max_cpu_cooler_height_mm']) ? (int) $specs['max_cpu_cooler_height_mm'] : null,
        ];
    }

    // TAMBAH: simpan build dari modal
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'notes'       => 'nullable|string',
            'components'  => 'required|array',
            'margin_pct'  => 'nullable|numeric|min:0',
            'total_modal' => 'nullable|integer|min:0',
            'harga_jual'  => 'nullable|integer|min:0',
        ]);

        $build = PcBuild::create([
            'name'        => $request->name,
            'notes'       => $request->notes,
            'components'  => $request->components,
            'margin_pct'  => $request->margin_pct  ?? 0,
            'total_price' => $request->total_modal  ?? 0,
            'harga_jual'  => $request->harga_jual   ?? 0,
            'created_by'  => auth()->id(),
        ]);

        return response()->json(['status' => 'success', 'build' => $build]);
    }

    // TAMBAH: untuk halaman transaksi — ambil semua saved build
    public function list()
    {
        $builds = PcBuild::with('creator')
            ->latest()
            ->get()
            ->map(fn($b) => [
                'id'            => $b->id,
                'name'          => $b->name,
                'notes'         => $b->notes,
                'total_price'   => $b->total_price,
                'total_fmt'     => 'Rp ' . number_format($b->total_price, 0, ',', '.'),
                'harga_jual'    => $b->harga_jual,                                           // ← baru
                'harga_jual_fmt'=> 'Rp ' . number_format($b->harga_jual, 0, ',', '.'),      // ← baru
                'margin_pct'    => $b->margin_pct,                                           // ← baru
                'status'        => $b->status,
                'created_by'    => $b->creator?->name,
                'components'    => $b->components,
                'created_at'    => $b->created_at->format('d M Y'),
            ]);

        return response()->json($builds);
    }

    // PcBuilderController.php
    public function updateStatus(Request $request, PcBuild $build)
    {
        $request->validate([
            'status' => 'required|in:draft,deal,cancelled'
        ]);

        if ($build->status === 'cancelled') {
            return response()->json(['message' => 'Build sudah cancelled, tidak bisa diubah.'], 422);
        }

        $build->update(['status' => $request->status]);

        return response()->json(['status' => 'success']);
    }
}
