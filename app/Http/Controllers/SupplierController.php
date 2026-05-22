<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Support\CacheVersions;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index (Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nama_supplier', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->paginate(10)->withQueryString();
        return view('supplier.index', compact('suppliers'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store (Request $request)
    {
        $validated = $request->validate( [
            'nama_supplier' => 'required|string|max:225',
            'alamat' => 'required|string',
        ]);

        Supplier::create($validated);
        CacheVersions::bumpCatalog();

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil ditambahkan');
    }

    public function edit (Supplier $supplier)
    {
        return view('supplier.edit', compact('supplier'));
    }

    public function update (Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'nama_supplier' => 'sometimes|required|string|max:225',
            'alamat' => 'sometimes|string',
        ]);

        $supplier->update($validated);
        CacheVersions::bumpCatalog();

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil diupdate');
    }

    public function destroy (Supplier $supplier)
    {
        $supplier->delete();
        CacheVersions::bumpCatalog();

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil dihapus');
    }
}
