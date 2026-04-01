@extends('layouts.app')

@section('title', 'Enterprise POS - PC Builder Edition')

@section('content')
    <div class="px-5 pb-10" x-data="posSystem()">

        {{-- 1. MODAL: PILIH SUPPLIER --}}
        <x-modal id="modalSupplier" title="Pilih Sumber Stok" size="md">
            <div class="space-y-3">
                {{-- HEADER INFO PRODUK --}}
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl mb-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pilih Gudang/Supplier untuk:
                    </p>
                    <p class="text-sm font-bold text-slate-800" x-text="selectedProduct.name"></p>
                </div>
                {{-- LIST SUPPLIER --}}
                <template x-for="source in selectedProduct.suppliers" :key="source.source_key">
                    <button @click="checkCompatibilityBeforeAdd(selectedProduct, source)"
                        class="w-full flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl hover:border-slate-900 transition group">
                        <div class="text-left">
                            <p class="text-sm font-black text-slate-800 group-hover:text-slate-900" x-text="source.name">
                            </p>
                            <p class="text-[10px] text-slate-400 uppercase font-bold mt-1"
                                x-text="'Stok: ' + source.pivot_stock + ' | Rp ' + formatNumber(source.pivot_price) + ' | ' + (source.condition || '-')">
                            </p>
                        </div>
                        <i class="fas fa-plus text-slate-300 group-hover:text-slate-900"></i>
                    </button>
                </template>
            </div>
        </x-modal>

        {{-- 2. MODAL: WARNING CONFLICT --}}
        <x-modal id="modalConflict" title="Conflict Alert!" size="sm">
            <div class="text-center p-2">
                {{-- ICON STATUS --}}
                <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-radiation text-2xl"></i>
                </div>
                {{-- DESKRIPSI KONFLIK --}}
                <h3 class="text-sm font-black text-slate-800 uppercase">Compatibility Warning</h3>
                <p class="text-[11px] text-slate-500 mt-2 leading-relaxed" x-text="conflictMessage"></p>
                {{-- AKSI --}}
                <div class="flex flex-col gap-2 mt-6">
                    <button @click="forceAddToCart()"
                        class="w-full py-3 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-200">Tetap
                        Tambahkan</button>
                    <button @click="closeModal('modalConflict')"
                        class="w-full py-3 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest">Batal</button>
                </div>
            </div>
        </x-modal>

        {{-- 3. MODAL: FINAL CHECKOUT --}}
        <x-modal id="modalCheckout" title="Finalisasi Transaksi" size="md">
            <div class="space-y-5">
                {{-- SALES AGENT --}}
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sales Agent</label>
                    <select x-model="transactionData.sales"
                        class="w-full mt-2 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm font-bold outline-none focus:border-slate-900">
                        <option value="" disabled>Pilih Sales</option>
                        @foreach ($salesUsers as $salesUser)
                            <option value="{{ $salesUser->name }}">{{ $salesUser->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- DATA CUSTOMER --}}
                <div class="pt-4 border-t border-slate-100">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Data Customer</label>
                    <div class="grid grid-cols-1 gap-3 mt-2">
                        <input type="text" x-model="transactionData.customerName" placeholder="Nama Lengkap Customer"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm outline-none focus:border-slate-900">
                        <input type="text" x-model="transactionData.customerPhone" placeholder="Nomor WhatsApp (628...)"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm outline-none focus:border-slate-900">
                    </div>
                </div>
                {{-- SERVICE FEE --}}
                <div class="pt-4 border-t border-slate-100">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Service Fee</label>
                    <input type="number" min="0" step="0.01" x-model.number="serviceFee" placeholder="0"
                        class="w-full mt-2 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm outline-none focus:border-slate-900">
                </div>
                {{-- DOKUMEN TRANSAKSI --}}
                <div class="pt-4 border-t border-slate-100">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dokumen Transaksi</label>
                    <select x-model="transactionData.type"
                        class="w-full mt-2 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm font-semibold outline-none focus:border-slate-900">
                        <option value="Invoice">Invoice</option>
                        <option value="Quotation">Quotation</option>
                        <option value="DO">Delivery Order</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-2">PDF akan otomatis diunduh setelah transaksi berhasil.</p>
                </div>
                {{-- RINGKASAN TOTAL --}}
                <div class="p-4 bg-slate-900 rounded-2xl text-white mt-4">
                    <div class="flex justify-between items-center text-[11px] text-slate-300 mb-1">
                        <span>Subtotal</span>
                        <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                    </div>
                    <div class="flex justify-between items-center text-[11px] text-slate-300 mb-3">
                        <span>Service Fee</span>
                        <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Total Tagihan</span>
                        <span class="text-xl font-black">Rp <span x-text="formatNumber(finalTotal)"></span></span>
                    </div>
                    <button @click="submitOrder()"
                        class="w-full mt-4 py-4 bg-emerald-500 text-white rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-emerald-600 transition shadow-lg shadow-emerald-900/20">
                        Simpan Transaksi
                    </button>
                </div>
            </div>
        </x-modal>

        {{-- MAIN INTERFACE --}}
        <div class="flex gap-8 items-start">
            <div class="flex-1 min-w-0">
                {{-- SEARCH + FILTER BAR --}}
                <div class="flex gap-4 items-center mb-8">
                    <div
                        class="flex-1 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-2 pr-4">
                        {{-- SEARCH INPUT --}}
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                            <input type="text" x-model="searchQuery" placeholder="Cari Part PC..."
                                class="w-full pl-12 pr-4 py-2 border-none outline-none focus:ring-0 text-sm font-medium">
                        </div>

                        {{-- CATEGORY DROPDOWN --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                class="flex items-center justify-between gap-3 px-5 py-2.5 bg-slate-50 border border-slate-100 rounded-xl shadow-sm hover:border-slate-300 transition min-w-[150px]">
                                <div class="flex flex-col items-start">
                                    <span
                                        class="text-[8px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Category</span>
                                    <span class="text-[11px] font-black text-slate-900 uppercase tracking-tighter"
                                        x-text="activeCat"></span>
                                </div>
                                <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform"
                                    :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute right-0 mt-2 w-48 bg-white border border-slate-100 rounded-2xl shadow-xl z-50 py-1 overflow-hidden">
                                <button @click="activeCat = 'Semua'; open = false"
                                    class="w-full text-left px-5 py-3 text-[11px] font-bold uppercase hover:bg-slate-50"
                                    :class="activeCat === 'Semua' ? 'text-blue-600 bg-blue-50' : 'text-slate-600'">Semua</button>
                                <template x-for="cat in categories" :key="cat.id">
                                    <button @click="activeCat = cat.name; open = false"
                                        class="w-full text-left px-5 py-3 text-[11px] font-bold uppercase hover:bg-slate-50 flex items-center justify-between"
                                        :class="activeCat === cat.name ? 'text-blue-600 bg-blue-50' : 'text-slate-600'">
                                        <span x-text="cat.name"></span>
                                        <i x-show="activeCat === cat.name" class="fas fa-check text-[10px]"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="h-8 w-px bg-slate-100 mx-2"></div>

                        {{-- COMPATIBILITY TOGGLE --}}
                        <button @click="filterCompatible = !filterCompatible"
                            :class="filterCompatible ? 'bg-emerald-50 text-emerald-600 border-emerald-100' :
                                'bg-slate-50 text-slate-400 border-slate-100'"
                            class="px-4 py-2.5 border rounded-xl text-[10px] font-black uppercase tracking-widest transition flex items-center gap-2">
                            <i class="fas fa-microchip"></i>
                            <span x-text="filterCompatible ? 'Compatibility: ON' : 'Compatibility: OFF'"></span>
                        </button>
                    </div>
                </div>

                {{-- PRODUCT GRID --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <template x-for="product in filteredProducts" :key="product.id">
                        {{-- PRODUCT CARD --}}
                        <div
                            class="bg-white rounded-4xl border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-xl hover:shadow-slate-200/50 transition duration-500">
                            {{-- PRODUCT IMAGE --}}
                            <div class="aspect-4/3 bg-slate-50 relative overflow-hidden cursor-zoom-in"
                                @click="openDetail(product)">
                                <img :src="product.image"
                                    class="w-full h-full object-cover group-hover:scale-110 transition duration-700"
                                    x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                            </div>
                            {{-- PRODUCT INFO --}}
                            <div class="p-6 flex-1 flex flex-col">
                                <div class="flex justify-between items-start mb-2">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]"
                                        x-text="product.category_name"></p>
                                    <button type="button" @click.stop="openDetail(product)"
                                        class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-900 hover:bg-slate-200 transition flex items-center justify-center relative z-10 pointer-events-auto"
                                        title="Lihat Detail" aria-label="Lihat Detail Produk">
                                        <i class="fas fa-info text-[10px]"></i>
                                    </button>
                                </div>
                                <h3 class="font-bold text-slate-800 text-[13px] h-10 line-clamp-2 leading-snug"
                                    x-text="product.name"></h3>
                                {{-- PRODUCT SPECS --}}
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="spec in getSpecs(product).slice(0, 2)" :key="spec.key + spec.value">
                                        <span
                                            class="text-[9px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                                            <span x-text="spec.key"></span>: <span x-text="spec.value"></span>
                                        </span>
                                    </template>
                                </div>
                                {{-- PRICE + ACTION --}}
                                <div class="mt-6 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8px] text-slate-400 font-bold uppercase">Price Start</p>
                                        <p class="text-sm font-black text-slate-900">Rp <span
                                                x-text="formatNumber(product.base_price)"></span></p>
                                    </div>
                                    <button @click="handleAddToCart(product)"
                                        class="w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center hover:bg-black transition-all hover:scale-110 shadow-lg shadow-slate-900/20">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- SIDEBAR CART --}}
            <div class="w-[400px] shrink-0 sticky top-6">
                <div
                    class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col h-[calc(100vh-60px)] overflow-hidden">
                    {{-- CART HEADER --}}
                    <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                        <h2 class="font-black text-slate-900 uppercase text-[12px] tracking-[0.3em]">Order Summary</h2>
                        <span class="text-[10px] font-black text-slate-400" x-text="cart.length + ' ITEMS'"></span>
                    </div>

                    {{-- CART ITEMS --}}
                    <div class="flex-1 overflow-y-auto p-8 space-y-6">
                        <template x-for="item in cart" :key="item.cartId">
                            <div class="flex gap-4">
                                <div
                                    class="w-16 h-16 bg-slate-50 rounded-2xl border border-slate-100 overflow-hidden shrink-0">
                                    <img :src="item.image" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start">
                                        <h4 class="text-[12px] font-bold text-slate-800 truncate pr-2" x-text="item.name">
                                        </h4>
                                        <button @click="removeFromCart(item.cartId)"
                                            class="text-slate-300 hover:text-red-500 transition"><i
                                                class="fas fa-times text-xs"></i></button>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[9px] font-black text-blue-500 uppercase"
                                            x-text="item.supplierName"></span>
                                        <template x-if="item.isConflict">
                                            <span
                                                class="text-[8px] font-black text-red-500 uppercase tracking-tighter">[CONFLICT]</span>
                                        </template>
                                    </div>
                                    {{-- QTY + LINE TOTAL --}}
                                    <div class="flex items-center justify-between mt-3">
                                        <div
                                            class="flex items-center gap-2 bg-slate-50 rounded-xl p-1 px-2 border border-slate-100">
                                            <button @click="updateQty(item.cartId, -1)"
                                                class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-black">-</button>
                                            <span class="text-[11px] font-black w-4 text-center" x-text="item.qty"></span>
                                            <button @click="updateQty(item.cartId, 1)"
                                                class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-black">+</button>
                                        </div>
                                        <p class="text-[12px] font-black text-slate-900">Rp <span
                                                x-text="formatNumber(item.price * item.qty)"></span></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- CART SUMMARY --}}
                    <div class="p-8 bg-slate-50 border-t border-slate-100 space-y-4">
                        <div class="flex justify-between items-center text-sm text-slate-500">
                            <span>Subtotal</span>
                            <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-slate-500">
                            <span>Service Fee</span>
                            <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                        </div>
                        <div class="pt-4 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-[14px] font-black text-slate-900 uppercase tracking-tighter">Total
                                Bill</span>
                            <span class="text-2xl font-black text-slate-900 tracking-tighter">Rp <span
                                    x-text="formatNumber(finalTotal)"></span></span>
                        </div>
                        <button @click="openModal('modalCheckout')" :disabled="cart.length === 0"
                            class="w-full py-5 bg-slate-900 text-white rounded-[1.5rem] font-black text-[11px] uppercase tracking-[0.2em] hover:bg-black transition disabled:opacity-30">
                            Checkout Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
        {{-- MODAL: PRODUCT DETAIL --}}
        <div x-show="detailOpen" x-transition x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeDetail()">
            <div class="bg-white w-full max-w-5xl rounded-3xl overflow-hidden shadow-2xl">
                {{-- MODAL HEADER --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Product Detail</p>
                        <h3 class="text-lg font-semibold text-slate-900 mt-1" x-text="detailProduct?.name"></h3>
                    </div>
                    <button @click="closeDetail()"
                        class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 hover:text-slate-900 hover:bg-slate-200 transition flex items-center justify-center">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
                {{-- MODAL BODY --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
                    {{-- IMAGE PREVIEW --}}
                    <div class="bg-slate-50 flex items-center justify-center p-6">
                        <div class="relative w-full">
                            <img :src="detailProduct?.image"
                                class="w-full h-[360px] object-contain rounded-2xl border border-slate-200 transition-transform duration-300"
                                :class="detailZoom ? 'scale-125 cursor-zoom-out' : 'cursor-zoom-in'"
                                @click="detailZoom = !detailZoom" x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                            {{-- FULLSCREEN ICON --}}
                            <button type="button" @click.stop="openImageViewer()"
                                class="absolute bottom-3 right-3 bg-white/90 text-slate-600 px-2.5 py-2 rounded-full border border-slate-200 flex items-center justify-center hover:bg-white transition"
                                aria-label="Lihat Gambar Penuh">
                                <i class="fas fa-expand text-[11px]"></i>
                            </button>
                        </div>
                    </div>
                    {{-- DETAIL INFO --}}
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Category</span>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600"
                                x-text="detailProduct?.category_name"></span>
                        </div>
                        {{-- SPECIFICATIONS --}}
                        <div class="mb-5">
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Specifications</p>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <template x-for="spec in getSpecs(detailProduct)" :key="spec.key + spec.value + 'detail'">
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1 w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                        <span class="font-semibold text-slate-700" x-text="spec.key"></span>
                                        <span class="text-slate-500">:</span>
                                        <span x-text="spec.value"></span>
                                    </li>
                                </template>
                                <template x-if="getSpecs(detailProduct).length === 0">
                                    <li class="text-slate-400">No specifications listed.</li>
                                </template>
                            </ul>
                        </div>
                        {{-- DESCRIPTION --}}
                        <div class="mb-6">
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Description</p>
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed"
                                x-text="detailProduct?.description || 'No description provided.'"></p>
                        </div>
                        {{-- CTA --}}
                        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Starting Price</p>
                                <p class="text-lg font-semibold text-slate-900">Rp <span
                                        x-text="formatNumber(detailProduct?.base_price || 0)"></span></p>
                            </div>
                            <button @click="handleAddToCart(detailProduct)"
                                class="px-5 py-3 rounded-xl bg-slate-900 text-white text-xs uppercase tracking-[0.2em] hover:bg-black transition">
                                Add To Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL: IMAGE FULLSCREEN --}}
        <div x-show="imageViewerOpen" x-transition x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-6"
            @click.self="closeImageViewer()">
            <div class="relative max-w-6xl w-full">
                {{-- FULLSCREEN IMAGE --}}
                <img :src="detailProduct?.image" class="w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl"
                    x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                {{-- CLOSE BUTTON --}}
                <button type="button" @click="closeImageViewer()"
                    class="absolute -top-4 -right-4 w-10 h-10 rounded-full bg-white text-slate-700 shadow-lg flex items-center justify-center hover:bg-slate-100 transition"
                    aria-label="Tutup">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function posSystem() {
                return {
                    // === FILTER STATE ===
                    searchQuery: '',
                    activeCat: 'Semua',
                    filterCompatible: false,
                    // === CHECKOUT STATE ===
                    serviceFee: 0,
                    conflictMessage: '',
                    // === MODAL STATE ===
                    detailOpen: false,
                    detailZoom: false,
                    imageViewerOpen: false,
                    detailProduct: null,
                    // === DATA SOURCES ===
                    categories: @json($categories),
                    products: @json($products).map(p => ({
                        id: p.id,
                        name: p.name,
                        category_name: p.category ? p.category.name : 'Uncategorized',
                        base_price: Number(p.base_price ?? 0),
                        socket: p.socket ?? null,
                        ram_type: p.ram_type ?? null,
                        image: p.image_url ?? @json(asset('assets/no-image.svg')),
                        description: p.description ?? null,
                        specs: p.specs ?? [],
                        suppliers: (p.suppliers || []).map(s => {
                            const sourceKey = s.pivot && s.pivot.id ? `pivot-${s.pivot.id}` :
                                `${s.supplier_id ?? s.id}-${s.pivot && s.pivot.condition ? s.pivot.condition : 'default'}`;

                            return {
                                id: s.id,
                                supplier_id: s.supplier_id ?? s.id,
                                source_key: sourceKey,
                                product_supplier_id: s.pivot ? s.pivot.id : null,
                                name: s.nama_supplier,
                                condition: s.pivot ? s.pivot.condition : null,
                                pivot_stock: Number(s.pivot ? s.pivot.stock : 0),
                                pivot_price: Number(s.pivot ? s.pivot.harga_jual_manual : (p.base_price ??
                                    0))
                            };
                        })
                    })),
                    // === CART STATE ===
                    cart: [],
                    selectedProduct: {
                        suppliers: []
                    },
                    tempSupplier: {},
                    // === TRANSACTION FORM ===
                    transactionData: {
                        sales: @js($salesUsers->first()?->name ?? ''),
                        customerName: '',
                        customerPhone: '',
                        type: 'Invoice'
                    },

                    // === COMPUTED: FILTERED PRODUCTS ===
                    get filteredProducts() {
                        return this.products.filter(p => {
                            const matchSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                            const matchCat = this.activeCat === 'Semua' || p.category_name === this.activeCat;
                            if (this.filterCompatible) {
                                return matchSearch && matchCat && !this.isProductIncompatible(p);
                            }
                            return matchSearch && matchCat;
                        });
                    },

                    // === COMPUTED: SUBTOTAL ===
                    get subtotal() {
                        return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                    },

                    // === COMPUTED: FINAL TOTAL ===
                    get finalTotal() {
                        return this.subtotal + (Number(this.serviceFee) || 0);
                    },

                    // === HELPER: NUMBER FORMAT ===
                    formatNumber(n) {
                        return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
                    },

                    // === SPEC NORMALIZER ===
                    getSpecs(p) {
                        if (!p) return [];
                        const specs = Array.isArray(p.specs) ? p.specs.map(s => ({
                            key: s.key,
                            value: s.value
                        })) : [];

                        if (specs.length === 0) {
                            if (p.socket) specs.push({
                                key: 'Socket',
                                value: p.socket
                            });
                            if (p.ram_type) specs.push({
                                key: 'RAM',
                                value: p.ram_type
                            });
                        }
                        return specs;
                    },

                    // === DETAIL MODAL ===
                    openDetail(product) {
                        this.detailProduct = product;
                        this.detailZoom = false;
                        this.imageViewerOpen = false;
                        this.detailOpen = true;
                    },

                    closeDetail() {
                        this.detailOpen = false;
                        this.detailZoom = false;
                        this.imageViewerOpen = false;
                        this.detailProduct = null;
                    },

                    // === IMAGE VIEWER ===
                    openImageViewer() {
                        if (!this.detailProduct) return;
                        this.imageViewerOpen = true;
                    },

                    closeImageViewer() {
                        this.imageViewerOpen = false;
                    },

                    // === COMPATIBILITY CHECK ===
                    isProductIncompatible(p) {
                        const cartProcie = this.cart.find(i => i.category_name === 'Processor' || i.category_name === 'CPU');
                        const cartMobo = this.cart.find(i => i.category_name === 'Motherboard');
                        const cartRam = this.cart.find(i => i.category_name === 'RAM' || i.category_name === 'Memory');

                        if ((p.category_name === 'Motherboard' && cartProcie && p.socket !== cartProcie.socket) ||
                            (p.category_name === 'Processor' && cartMobo && p.socket !== cartMobo.socket)) return true;

                        if ((p.category_name === 'RAM' && cartMobo && p.ram_type !== cartMobo.ram_type) ||
                            (p.category_name === 'Motherboard' && cartRam && p.ram_type !== cartRam.ram_type)) return true;

                        return false;
                    },

                    // === CART: ADD ===
                    handleAddToCart(product) {
                        this.selectedProduct = product;
                        if (!product.suppliers || product.suppliers.length === 0) {
                            alert('Stok dari supplier tidak tersedia.');
                            return;
                        }
                        if (product.suppliers.length === 1) {
                            this.checkCompatibilityBeforeAdd(product, product.suppliers[0]);
                            return;
                        }
                        openModal('modalSupplier');
                    },

                    // === CART: PRECHECK CONFLICT ===
                    checkCompatibilityBeforeAdd(p, s) {
                        const cartProcie = this.cart.find(i => i.category_name === 'Processor' || i.category_name === 'CPU');
                        const cartMobo = this.cart.find(i => i.category_name === 'Motherboard');

                        let conflict = false;
                        if ((p.category_name === 'Motherboard' && cartProcie && p.socket !== cartProcie.socket) ||
                            (p.category_name === 'Processor' && cartMobo && p.socket !== cartMobo.socket)) {
                            this.conflictMessage = `Socket ${p.socket} tidak cocok dengan build saat ini.`;
                            conflict = true;
                        }

                        if (conflict) {
                            this.tempSupplier = s;
                            openModal('modalConflict');
                        } else {
                            this.confirmAddToCart(p, s, false);
                        }
                    },

                    // === CART: FORCE ADD ===
                    forceAddToCart() {
                        this.confirmAddToCart(this.selectedProduct, this.tempSupplier, true);
                        closeModal('modalConflict');
                    },

                    // === CART: CONFIRM ADD ===
                    confirmAddToCart(p, s, isConflict) {
                        const cartId = `${p.id}-${s.source_key}`;
                        const found = this.cart.find(i => i.cartId === cartId);

                        if (found) {
                            if (found.qty + 1 > s.pivot_stock) return alert('Stok habis.');
                            found.qty++;
                        } else {
                            this.cart.push({
                                ...p,
                                cartId,
                                source_key: s.source_key,
                                supplier_id: s.supplier_id,
                                product_supplier_id: s.product_supplier_id,
                                supplierName: s.condition ? `${s.name} (${s.condition})` : s.name,
                                price: s.pivot_price,
                                qty: 1,
                                isConflict: isConflict
                            });
                        }
                        closeModal('modalSupplier');
                    },

                    // === CART: UPDATE QTY ===
                    updateQty(cartId, delta) {
                        const item = this.cart.find(i => i.cartId === cartId);
                        if (!item) return;

                        const newQty = item.qty + delta;
                        const productSource = this.products.find(p => p.id === item.id);
                        if (!productSource) return;

                        const supplierSource = productSource.suppliers.find(s => s.source_key === item.source_key);
                        if (!supplierSource) return;

                        if (newQty > supplierSource.pivot_stock) return alert('Stok supplier tidak cukup.');
                        if (newQty <= 0) return this.removeFromCart(cartId);

                        item.qty = newQty;
                    },

                    // === CART: REMOVE ===
                    removeFromCart(cartId) {
                        this.cart = this.cart.filter(i => i.cartId !== cartId);
                    },

                    // === SUBMIT ORDER ===
                    async submitOrder() {
                        if (!this.transactionData.sales) return alert('Sales wajib dipilih.');
                        if (!this.transactionData.customerName) return alert('Nama customer wajib diisi.');

                        const payload = {
                            transaction_data: this.transactionData,
                            service_fee: this.serviceFee,
                            cart: this.cart.map(i => ({
                                product_id: i.id,
                                supplier_id: i.supplier_id,
                                product_supplier_id: i.product_supplier_id,
                                qty: i.qty,
                                price: i.price,
                                is_conflict: i.isConflict
                            }))
                        };

                        try {
                            const response = await fetch("{{ route('transactions.store') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(payload)
                            });

                            const result = await response.json();
                            if (response.ok && result.status === 'success') {
                                if (result.document_url) {
                                    const iframe = document.createElement('iframe');
                                    iframe.style.display = 'none';
                                    iframe.src = result.document_url;
                                    document.body.appendChild(iframe);
                                }

                                setTimeout(() => {
                                    window.location.href = result.redirect;
                                }, 800);
                            } else {
                                alert('Gagal: ' + (result.message || 'Terjadi kesalahan.'));
                            }
                        } catch (error) {
                            alert('Tidak dapat terhubung ke server.');
                        }
                    }
                }
            }
        </script>
    @endpush
@endsection
