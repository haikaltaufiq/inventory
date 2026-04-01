<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();

        // 1. Inisialisasi Query dengan Sum Stock dari pivot
        // asumsikan nama table pivotnya adalah product_supplier
        $query = Product::with(['suppliers', 'category'])
            ->withSum('suppliers as total_stok_count', 'product_supplier.stock');

        // 2. Filter Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // 3. Filter Kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 4. Hitung KPI dari data yang difilter
        $filteredData = (clone $query)->get();

        $summary = [
            'total_produk' => $filteredData->count(),
            'total_stok'   => 0,
            'nilai_inv'    => 0,
            'stok_menipis' => 0,
        ];

        foreach ($filteredData as $p) {
            foreach ($p->suppliers as $s) {
                $stok = $s->pivot->stock;
                $summary['total_stok'] += $stok;
                $summary['nilai_inv'] += ($stok * $s->pivot->harga_beli);

                if ($stok <= 10) {
                    $summary['stok_menipis']++;
                }
            }
        }

        $products = $query->paginate(10)->withQueryString();

        return view('products.index', compact('products', 'summary', 'categories'));
    }


    public function create()
    {
        // Ambil data buat dropdown di form
        $categories = Category::all();
        $suppliers = Supplier::all();
        return view('products.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'selling_price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string',
            'specs' => 'nullable|array',
            'suppliers' => 'required|array',
            'suppliers.*.supplier_id' => 'required|exists:suppliers,id',
            'suppliers.*.stock' => 'required|integer|min:0',
            'suppliers.*.harga_beli' => 'required|numeric',
            'suppliers.*.harga_jual' => 'nullable|numeric',
            'suppliers.*.condition' => 'required|string|in:New,Used,Refurbished',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $imageUrl = Storage::url($path);
            }

            // 1. Simpan ke table products
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'selling_price' => $validated['selling_price'],
                'description' => $validated['description'],
                'warranty' => $request->warranty,
                'image_url' => Schema::hasColumn('products', 'image_url') ? $imageUrl : null,
            ]);

            // 2. Simpan Spek (Kompatibilitas)
            if ($request->has('specs')) {
                foreach ($request->specs as $spec) {
                    if (!empty($spec['key']) && !empty($spec['value'])) {
                        $product->specifications()->create([
                            'spec_key' => $spec['key'],
                            'spec_value' => $spec['value'],
                        ]);
                    }
                }
            }

            // 3. Simpan ke Pivot Product_Supplier
            $processedSuppliers = [];

            foreach ($validated['suppliers'] as $sup) {
                $key = $sup['supplier_id'] . '-' . ($sup['condition'] ?? 'New');

                if (isset($processedSuppliers[$key])) {
                    $processedSuppliers[$key]['stock'] += $sup['stock'];
                } else {
                    $processedSuppliers[$key] = [
                        'supplier_id' => $sup['supplier_id'],
                        'condition' => $sup['condition'] ?? 'New',
                        'stock' => $sup['stock'],
                        'harga_beli' => $sup['harga_beli'],
                        'harga_jual_manual' => $sup['harga_jual'] ?? $validated['selling_price'],
                    ];
                }
            }

            foreach ($processedSuppliers as $data) {
                $product->suppliers()->attach($data['supplier_id'], [
                    'condition'         => $data['condition'],
                    'stock'             => $data['stock'],
                    'harga_beli'        => $data['harga_beli'],
                    'harga_jual_manual' => $data['harga_jual_manual'],
                    'entry_date'        => now(),
                ]);
            }
            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambah');
        });
    }

    public function edit(Product $product)
    {
        $product->load(['suppliers', 'specifications', 'category']);
        $categories = Category::all();
        $suppliers = Supplier::all();

        return view('products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'selling_price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string',
            'specs' => 'nullable|array',
            'suppliers' => 'required|array|min:1',
            'suppliers.*.supplier_id' => 'required|exists:suppliers,id',
            'suppliers.*.stock' => 'required|integer|min:0',
            'suppliers.*.harga_beli' => 'required|numeric',
            'suppliers.*.harga_jual' => 'nullable|numeric',
            'suppliers.*.condition' => 'required|string|in:New,Used,Refurbished',
        ]);

        return DB::transaction(function () use ($request, $validated, $product) {
            $imageUrl = $product->image_url;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $imageUrl = Storage::url($path);

                if (!empty($product->image_url)) {
                    $oldPath = ltrim(str_replace('/storage/', '', $product->image_url), '/');
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // 1. Update data utama produk
            $product->update([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'selling_price' => $validated['selling_price'],
                'description' => $validated['description'],
                'warranty' => $request->warranty,
                'image_url' => Schema::hasColumn('products', 'image_url') ? $imageUrl : $product->image_url,
            ]);

            $product->specifications()->delete();
            if ($request->has('specs')) {
                foreach ($request->specs as $spec) {
                    if (!empty($spec['key']) && !empty($spec['value'])) {
                        $product->specifications()->create([
                            'spec_key' => $spec['key'],
                            'spec_value' => $spec['value'],
                        ]);
                    }
                }
            }

            // 3. Update Supplier Pivot
            // Kita cabut dulu semua koneksi supplier lama
            $product->suppliers()->detach();

            $processedSuppliers = [];
            foreach ($validated['suppliers'] as $sup) {
                $key = $sup['supplier_id'] . '-' . ($sup['condition'] ?? 'New');

                if (isset($processedSuppliers[$key])) {
                    $processedSuppliers[$key]['stock'] += $sup['stock'];
                } else {
                    $processedSuppliers[$key] = [
                        'supplier_id' => $sup['supplier_id'],
                        'condition' => $sup['condition'] ?? 'New',
                        'stock' => $sup['stock'],
                        'harga_beli' => $sup['harga_beli'],
                        'harga_jual_manual' => $sup['harga_jual'] ?? $validated['selling_price'],
                    ];
                }
            }

            foreach ($processedSuppliers as $data) {
                $product->suppliers()->attach($data['supplier_id'], [
                    'condition'         => $data['condition'],
                    'stock'             => $data['stock'],
                    'harga_beli'        => $data['harga_beli'],
                    'harga_jual_manual' => $data['harga_jual_manual'],
                    'entry_date'        => now(), // Atau tetep pake date lama kalau mau tracking
                ]);
            }

            return redirect()->route('products.index')->with('success', 'Produk berhasil diupdate');
        });
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus');
    }

    public function reportProduct()
    {
        return view('laporan-product.index');
    }
}
