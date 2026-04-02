<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class PcBuilderController extends Controller
{
    public function index()
    {
        return view('pc-builder.index');
    }

    /**
     * Endpoint AJAX untuk PC Builder.
     * Dipanggil setiap kali user memilih komponen.
     *
     * Alur baru (Motherboard-first):
     *   1. Motherboard dipilih bebas (tidak ada filter)
     *   2. CPU difilter by socket dari Motherboard
     *   3. RAM difilter by ram_type dari Motherboard
     *   4. VGA, Storage bebas dipilih setelah CPU ada
     *   5. PSU bebas dipilih setelah CPU + VGA ada
     *
     * Query params yang diterima:
     *   - type     : nama kategori yang dicari (misal: 'Motherboard', 'Processor', 'RAM')
     *   - socket   : filter socket — sekarang dikirim dari Motherboard untuk filter CPU
     *   - ram_type : filter tipe RAM — dikirim dari Motherboard untuk filter RAM
     *
     * Response: JSON array of products dengan spec-nya
     */
    public function getCompatible(Request $request)
    {
        $type    = $request->type;      // Nama kategori yang dicari
        $socket  = $request->socket;    // Nilai socket (dari Motherboard yang dipilih)
        $ramType = $request->ram_type;  // Nilai ram_type (dari Motherboard yang dipilih)

        // Cari produk berdasarkan kategori
        $query = Product::whereHas('category', function ($q) use ($type) {
            $q->where('name', $type);
        })->with(['specifications', 'suppliers']);

        // ============================================================
        // FILTER SOCKET
        // Sebelumnya: hanya untuk Motherboard (filter dari CPU)
        // Sekarang: untuk Processor juga (filter dari Motherboard)
        // ============================================================
        if ($socket && in_array($type, ['Processor', 'Motherboard', 'CPU Cooler'])) {
            $query->whereHas('specifications', function ($q) use ($socket) {
                $q->where('spec_key', 'socket')
                  ->where('spec_value', $socket);
            });
        }

        // ============================================================
        // FILTER RAM TYPE
        // Tidak berubah — RAM tetap difilter by ram_type dari Motherboard
        // ============================================================
        if ($ramType && $type === 'RAM') {
            $query->whereHas('specifications', function ($q) use ($ramType) {
                $q->where('spec_key', 'ram_type')
                  ->where('spec_value', $ramType);
            });
        }

        // Hanya tampilkan produk yang punya stok > 0
        $query->whereHas('suppliers', function ($q) {
            $q->where('product_supplier.stock', '>', 0);
        });

        $products = $query->get()->map(function ($product) {
            // Ubah specifications collection jadi key-value array
            // supaya mudah dibaca di JavaScript: specs.socket, specs.ram_type, dll
            $specs = $product->specifications->pluck('spec_value', 'spec_key')->toArray();

            // Ambil harga jual terbaik dari supplier yang punya stok
            $hargaJual = $product->suppliers
                ->filter(fn($s) => $s->pivot->stock > 0)
                ->min(fn($s) => $s->pivot->harga_jual_manual)
                ?? 0;

            return [
                'id'        => $product->id,
                'name'      => $product->name,
                'price'     => $hargaJual,
                'price_fmt' => 'Rp ' . number_format($hargaJual, 0, ',', '.'),
                'specs'     => $specs,

                // Expose spec penting ke level atas untuk chaining kompatibilitas di JS
                // Motherboard: punya socket (untuk filter CPU) dan ram_type (untuk filter RAM)
                // CPU: punya socket (untuk validasi) dan tdp (untuk estimasi PSU)
                // VGA: punya tdp (untuk estimasi PSU)
                // PSU: punya wattage (untuk validasi kecukupan daya)
                'socket'   => $specs['socket']   ?? null,
                'ram_type' => $specs['ram_type'] ?? null,
                'tdp'      => $specs['tdp']      ?? null,
                'wattage'  => $specs['wattage']  ?? null,
            ];
        });

        return response()->json($products);
    }

    /**
     * Endpoint khusus untuk ambil semua Motherboard.
     * Ini adalah entry point baru PC Builder — tidak butuh filter apapun.
     * Sebelumnya entry point adalah CPU (getCpuList),
     * sekarang diganti dengan Motherboard.
     */
    public function getMotherboardList()
    {
        return $this->getCompatible(new Request(['type' => 'Motherboard']));
    }
}
