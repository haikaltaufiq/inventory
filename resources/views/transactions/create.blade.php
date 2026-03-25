@extends('layouts.app')

@section('title', 'Tambah Transaksi')

@section('content')
<div class="p-6">
    <h1 class="text-3xl font-bold mb-6">Tambah Transaksi</h1>

    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <form action="{{ route('transactions.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Customer</label>
                <select name="customer_id" class="w-full px-4 py-2 border rounded-lg @error('customer_id') border-red-500 @enderror" required>
                    <option value="">Pilih Customer</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }} - {{ $customer->email }}
                    </option>
                    @endforeach
                </select>
                @error('customer_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Produk</label>
                <select name="product_id" id="product_id" class="w-full px-4 py-2 border rounded-lg @error('product_id') border-red-500 @enderror" required>
                    <option value="">Pilih Produk</option>
                    @foreach($products as $product)
                        @php
                            $totalStock = $product->suppliers->sum('pivot.stock');
                        @endphp
                        @if($totalStock > 0)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} (Total Stok: {{ $totalStock }})
                        </option>
                        @endif
                    @endforeach
                </select>
                @error('product_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4" id="supplier-section" style="display: none;">
                <label class="block text-gray-700 font-semibold mb-2">Pilih Supplier</label>
                <select name="supplier_id" id="supplier_id" class="w-full px-4 py-2 border rounded-lg @error('supplier_id') border-red-500 @enderror" required>
                    <option value="">Pilih Supplier</option>
                </select>
                @error('supplier_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <div id="supplier-info" class="mt-2 p-3 bg-blue-50 rounded text-sm" style="display: none;">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-gray-600">Stok Tersedia:</span>
                            <span class="font-semibold" id="supplier-stock">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Harga Jual:</span>
                            <span class="font-semibold" id="supplier-price">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Jumlah</label>
                <input
                    type="number"
                    name="quantity"
                    id="quantity"
                    value="{{ old('quantity', 1) }}"
                    min="1"
                    class="w-full px-4 py-2 border rounded-lg @error('quantity') border-red-500 @enderror"
                    required
                >
                @error('quantity')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Tipe Transaksi</label>
                <select name="type" class="w-full px-4 py-2 border rounded-lg @error('type') border-red-500 @enderror" required>
                    <option value="">Pilih Tipe</option>
                    <option value="Invoice" {{ old('type') == 'Invoice' ? 'selected' : '' }}>Invoice</option>
                    <option value="Quotation" {{ old('type') == 'Quotation' ? 'selected' : '' }}>Quotation</option>
                    <option value="DO" {{ old('type') == 'DO' ? 'selected' : '' }}>Delivery Order</option>
                </select>
                @error('type')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Tanggal Transaksi</label>
                <input
                    type="date"
                    name="transaction_date"
                    value="{{ old('transaction_date', date('Y-m-d')) }}"
                    class="w-full px-4 py-2 border rounded-lg @error('transaction_date') border-red-500 @enderror"
                    required
                >
                @error('transaction_date')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 p-4 bg-gray-100 rounded-lg">
                <p class="font-semibold">Estimasi Total: <span id="total_estimate" class="text-blue-600">Rp 0</span></p>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    Simpan
                </button>
                <a href="{{ route('transactions.index') }}" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400 text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let suppliersData = [];

document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const supplierSection = document.getElementById('supplier-section');
    const supplierSelect = document.getElementById('supplier_id');
    const supplierInfo = document.getElementById('supplier-info');
    const quantityInput = document.getElementById('quantity');
    const totalEstimate = document.getElementById('total_estimate');

    // When product changes, load suppliers
    productSelect.addEventListener('change', async function() {
        const productId = this.value;

        if (!productId) {
            supplierSection.style.display = 'none';
            supplierInfo.style.display = 'none';
            supplierSelect.innerHTML = '<option value="">Pilih Supplier</option>';
            return;
        }

        try {
            const response = await fetch(`/api/products/${productId}/suppliers`);
            suppliersData = await response.json();

            supplierSelect.innerHTML = '<option value="">Pilih Supplier</option>';

            suppliersData.forEach(supplier => {
                const option = document.createElement('option');
                option.value = supplier.id;
                option.textContent = `${supplier.nama_supplier} (Stok: ${supplier.stock}, Harga: Rp ${parseInt(supplier.harga_jual).toLocaleString('id-ID')})`;
                option.dataset.stock = supplier.stock;
                option.dataset.price = supplier.harga_jual;
                supplierSelect.appendChild(option);
            });

            supplierSection.style.display = 'block';
        } catch (error) {
            console.error('Error loading suppliers:', error);
            alert('Gagal memuat data supplier');
        }
    });

    // When supplier changes, show info
    supplierSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (!this.value) {
            supplierInfo.style.display = 'none';
            return;
        }

        const stock = selectedOption.dataset.stock;
        const price = selectedOption.dataset.price;

        document.getElementById('supplier-stock').textContent = stock;
        document.getElementById('supplier-price').textContent = 'Rp ' + parseInt(price).toLocaleString('id-ID');

        supplierInfo.style.display = 'block';
        quantityInput.max = stock;

        updateTotal();
    });

    // Update total calculation
    function updateTotal() {
        const selectedSupplier = supplierSelect.options[supplierSelect.selectedIndex];
        if (!selectedSupplier || !selectedSupplier.value) {
            totalEstimate.textContent = 'Rp 0';
            return;
        }

        const price = parseFloat(selectedSupplier.dataset.price) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const total = price * quantity;

        totalEstimate.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    quantityInput.addEventListener('input', updateTotal);
});
</script>
@endsection
