<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PcBuilderController extends Controller
{
    // =========================================================================
    // CANONICAL SPEC KEYS
    // Harus persis sama dengan spec_key yang tersimpan di product_specifications
    // dan yang didefinisikan di config/product_specs.php.
    // =========================================================================
    private const COMPAT_KEYS = [
        'socket_type'   => 'socket_type',   // Mobo ↔ CPU & CPU Cooler
        'ram_type'      => 'ram_type',       // Mobo (via ram_type_slot) ↔ RAM
        'total_wattage' => 'total_wattage',  // PSU — validasi kecukupan daya
    ];

    // =========================================================================
    // CATEGORY MAP
    // Resolve nama kategori dari JS ke nama yang ada di tabel categories.
    // Solusi untuk inkonsistensi nama: VGA / GPU / VGA Card, dst.
    // =========================================================================
    private const CATEGORY_MAP = [
        'Motherboard'  => ['Motherboard'],
        'Processor'    => ['Processor'],
        'RAM'          => ['RAM'],
        'VGA'          => ['VGA', 'GPU', 'VGA Card'],
        'Storage'      => ['Storage', 'Storage (SSD/HDD)'],
        'Power Supply' => ['Power Supply', 'Power_Supply'],
        'CPU Cooler'   => ['CPU Cooler'],
        'Casing'       => ['Casing'],
    ];

    public function index()
    {
        return view('pc-builder.index');
    }

    // =========================================================================
    // GET COMPATIBLE
    //
    // Satu endpoint AJAX untuk semua jenis komponen.
    //
    // Query params yang diterima:
    //   type         - nama kategori (Motherboard / Processor / RAM / VGA / dst)
    //   socket_type  - filter Processor & CPU Cooler berdasarkan socket
    //   ram_type     - filter RAM berdasarkan tipe (value dari ram_type_slot mobo)
    //   min_wattage  - filter PSU, hanya tampilkan total_wattage >= nilai ini
    // =========================================================================
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

        $categoryNames = self::CATEGORY_MAP[$type] ?? [$type];

        $query = Product::query()
            ->whereHas('category', fn($q) => $q->whereIn('name', $categoryNames))
            ->with(['specifications', 'suppliers']);

        // =====================================================================
        // FILTER SOCKET TYPE
        // Diterapkan ke Processor dan CPU Cooler.
        // Value-nya dari mobo.socket_type yang dikirim JS.
        // =====================================================================
        if ($socketType && in_array($type, ['Processor', 'CPU Cooler'], true)) {
            $query->whereHas('specifications', fn($q) =>
                $q->where('spec_key', self::COMPAT_KEYS['socket_type'])
                  ->where('spec_value', $socketType)
            );
        }

        // =====================================================================
        // FILTER RAM TYPE
        // Diterapkan ke RAM.
        // Value-nya dari mobo.ram_type_slot, dicocokkan ke ram_type di tabel RAM.
        // =====================================================================
        if ($ramType && $type === 'RAM') {
            $query->whereHas('specifications', fn($q) =>
                $q->where('spec_key', self::COMPAT_KEYS['ram_type'])
                  ->where('spec_value', $ramType)
            );
        }

        // =====================================================================
        // FILTER PSU WATTAGE
        // spec_value tersimpan sebagai string → CAST ke UNSIGNED untuk
        // perbandingan numerik yang benar.
        // =====================================================================
        if ($minWattage && $type === 'Power Supply') {
            $query->whereHas('specifications', fn($q) =>
                $q->where('spec_key', self::COMPAT_KEYS['total_wattage'])
                  ->whereRaw('CAST(spec_value AS UNSIGNED) >= ?', [$minWattage])
            );
        }

        // Hanya produk dengan stok > 0
        $query->whereHas('suppliers', fn($q) =>
            $q->where('product_supplier.stock', '>', 0)
        );

        return response()->json(
            $query->get()->map(fn($p) => $this->formatProduct($p))
        );
    }

    // =========================================================================
    // FORMAT PRODUCT
    //
    // Konversi Eloquent model ke array yang dikonsumsi JavaScript.
    //
    // Spec penting di-expose ke level atas supaya JS bisa baca langsung
    // sebagai product.socket_type, product.ram_type_slot, dst.
    // (bukan product.specs.socket_type — lebih bersih dan tidak ambigu)
    // =========================================================================
    private function formatProduct($product): array
    {
        $specs = $product->specifications
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

            // Mobo
            'socket_type'   => $specs['socket_type']   ?? null,
            'ram_type_slot' => $specs['ram_type_slot'] ?? null,
            // CPU
            'tdp_watt'      => isset($specs['tdp_watt'])      ? (int) $specs['tdp_watt']      : null,
            // RAM
            'ram_type'      => $specs['ram_type']      ?? null,
            // VGA
            'min_psu_watt'  => isset($specs['min_psu_watt'])  ? (int) $specs['min_psu_watt']  : null,
            // PSU
            'total_wattage' => isset($specs['total_wattage']) ? (int) $specs['total_wattage'] : null,
        ];
    }
}   
