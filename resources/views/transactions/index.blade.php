@extends('layouts.app')

@section('title', 'Enterprise POS - PC Builder Edition')

@section('content')
<div class="px-5 pb-10" x-data="posSystem()">

    {{-- 1. MODAL: PILIH SUPPLIER --}}
    <x-modal id="modalSupplier" title="Pilih Sumber Stok" size="md">
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                <p class="text-sm font-semibold text-slate-900" x-text="selectedProduct.name"></p>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200"
                        x-text="selectedProduct.category_name || 'Uncategorized'"></span>
                    <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">
                        Harga mulai Rp <span x-text="formatNumber(selectedProduct.base_price || 0)"></span>
                    </span>
                </div>
                <p class="mt-3 text-sm text-slate-500">Pilih supplier dengan stok dan kondisi yang paling sesuai.</p>
            </div>

            <div class="space-y-3">
                <template x-for="source in selectedProduct.suppliers" :key="source.source_key">
                    <button @click="checkCompatibilityBeforeAdd(selectedProduct, source)"
                        class="w-full rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 hover:bg-slate-50/70">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900" x-text="source.name"></span>
                                    <span x-show="source.condition"
                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500"
                                        x-text="source.condition"></span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-xs text-slate-400">Stok tersedia</p>
                                        <p class="mt-1 font-semibold text-slate-800" x-text="source.pivot_stock"></p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-xs text-slate-400">Harga jual</p>
                                        <p class="mt-1 font-semibold text-slate-800">Rp <span
                                                x-text="formatNumber(source.pivot_price)"></span></p>
                                    </div>
                                </div>
                            </div>
                            <span
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                                <i class="fas fa-arrow-right text-sm"></i>
                            </span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </x-modal>

    {{-- 2. MODAL: WARNING CONFLICT --}}
    <x-modal id="modalConflict" title="Compatibility Check" size="sm">
        <div class="text-center p-2">
            {{-- ICON STATUS --}}
            <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            {{-- DESKRIPSI KONFLIK --}}
            <h3 class="text-base font-semibold text-slate-900">Produk mungkin tidak kompatibel</h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed" x-text="conflictMessage"></p>
            {{-- AKSI --}}
            <div class="flex flex-col gap-2 mt-6">
                <button @click="forceAddToCart()"
                    class="w-full py-3 bg-slate-900 text-white rounded-xl text-sm font-medium hover:bg-slate-800 transition">Tetap
                    Tambahkan</button>
                <button @click="closeModal('modalConflict')"
                    class="w-full py-3 bg-slate-100 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-200 transition">Batal</button>
            </div>
        </div>
    </x-modal>

    {{-- 3. MODAL: FINAL CHECKOUT --}}
    <div id="modalCheckout"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4 md:p-6">
        <div class="my-auto w-full max-w-4xl overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4 md:px-6">
                <div>
                    <p class="text-xs text-slate-500">Finalisasi transaksi</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">Konfirmasi detail order</h2>
                </div>
                <button onclick="closeModal('modalCheckout')"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <div class="max-h-[calc(100vh-8rem)] overflow-y-auto px-5 py-5 md:px-6 md:py-6">
                <div class="grid gap-5 lg:grid-cols-[1.15fr,0.85fr]">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <label class="text-sm font-medium text-slate-600">Sales</label>
                            <select x-model="transactionData.sales"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                                <option value="" disabled>Pilih Sales</option>
                                @foreach ($salesUsers as $salesUser)
                                <option value="{{ $salesUser->name }}">{{ $salesUser->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <label class="text-sm font-medium text-slate-600">Customer</label>
                            <div class="mt-2 grid grid-cols-1 gap-3">
                                <input type="text" x-model="transactionData.customerName" placeholder="Nama Lengkap Customer"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                                <input type="text" x-model="transactionData.customerPhone" placeholder="Nomor WhatsApp (628...)"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                                <textarea x-model="transactionData.customerAddress" rows="2" placeholder="Alamat Customer"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400"></textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-medium text-slate-600">Biaya tambahan - Instalasi</label>
                                <input type="number" min="0" step="0.01" x-model.number="additionalFees.installation" placeholder="0"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-medium text-slate-600">Biaya tambahan - Jasa layanan</label>
                                <input type="number" min="0" step="0.01" x-model.number="additionalFees.service_labor" placeholder="0"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-medium text-slate-600">Biaya tambahan - Ongkos kirim</label>
                                <input type="number" min="0" step="0.01" x-model.number="additionalFees.shipping" placeholder="0"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-medium text-slate-600">Biaya tambahan - Marketing</label>
                                <input type="number" min="0" step="0.01" x-model.number="additionalFees.marketing" placeholder="0"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-medium text-slate-600">Dokumen transaksi</label>
                                <select x-model="transactionData.type"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                                    <option value="Invoice">Invoice</option>
                                    <option value="Quotation">Quotation</option>
                                    <option value="DO">Delivery Order</option>
                                </select>
                                <p class="mt-2 text-xs text-slate-400">Dokumen PDF akan otomatis diunduh setelah transaksi berhasil disimpan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 lg:p-5">
                        <p class="text-sm font-medium text-slate-700">Ringkasan transaksi</p>
                        <p class="mt-1 text-sm text-slate-500">Pastikan detail customer, dokumen, dan total sudah sesuai sebelum disimpan.</p>

                        <div class="mt-5 space-y-3">
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>Subtotal</span>
                                <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>Biaya tambahan</span>
                                <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                                <span class="text-sm font-medium text-slate-600">Total tagihan</span>
                                <span class="text-2xl font-semibold text-slate-900">Rp <span x-text="formatNumber(finalTotal)"></span></span>
                            </div>
                        </div>

                        <button @click="submitOrder()"
                            class="mt-5 w-full rounded-xl bg-slate-900 py-3.5 text-sm font-medium text-white transition hover:bg-slate-800">
                            Simpan Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-modal id="modalBuildName" title="Nama Barang Rakit PC" size="sm">
        <div class="space-y-4">
            <p class="text-sm text-slate-500">Masukkan nama barang utama untuk transaksi Rakit PC.</p>
            <input type="text" x-model="draftBuildName" placeholder="Contoh: PC Gaming"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
            <div class="flex justify-end gap-2">
                <button @click="closeModal('modalBuildName')"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Batal</button>
                <button @click="applyBuildName()"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Simpan</button>
            </div>
        </div>
    </x-modal>

    {{-- MAIN INTERFACE --}}
    <div class="flex gap-8 items-start">
        <div class="flex-1 min-w-0">
            {{-- SEARCH + FILTER BAR --}}
            <div class="mb-8">
                <div
                    class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-3 shadow-sm lg:flex-row lg:items-center">
                    {{-- SEARCH INPUT --}}
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari Part PC..."
                            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:bg-white">
                    </div>

                    {{-- CATEGORY DROPDOWN --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="flex h-11 min-w-[13rem] items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 transition hover:border-slate-300 hover:bg-white">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="text-sm text-slate-400"><i class="fas fa-layer-group"></i></span>
                                <span class="truncate" x-text="activeCat"></span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl">
                            <button @click="activeCat = 'Semua'; open = false"
                                class="flex w-full items-center justify-between px-4 py-3 text-left text-sm hover:bg-slate-50"
                                :class="activeCat === 'Semua' ? 'bg-slate-50 text-slate-900' : 'text-slate-600'">Semua</button>
                            <template x-for="cat in categories" :key="cat.id">
                                <button @click="activeCat = cat.name; open = false"
                                    class="flex w-full items-center justify-between px-4 py-3 text-left text-sm hover:bg-slate-50"
                                    :class="activeCat === cat.name ? 'bg-slate-50 text-slate-900' : 'text-slate-600'">
                                    <span x-text="cat.name"></span>
                                    <i x-show="activeCat === cat.name" class="fas fa-check text-[10px]"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- COMPATIBILITY TOGGLE --}}
                    <button @click="toggleCompatibility()"
                        :class="filterCompatible ? 'bg-slate-900 text-white border-slate-900' :
                                'bg-slate-50 text-slate-600 border-slate-200'"
                        class="flex h-11 items-center gap-2 rounded-xl border px-4 text-sm transition hover:border-slate-300">
                        <i class="fas fa-microchip"></i>
                        <span x-text="filterCompatible ? 'Compatibility aktif' : 'Match compatibility'"></span>
                    </button>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button @click="setTransactionMode('sparepart')"
                        :class="transactionData.transactionMode === 'sparepart' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200'"
                        class="rounded-xl border px-4 py-2 text-sm transition">
                        Sparepart only
                    </button>
                    <button @click="setTransactionMode('rakit_pc')"
                        :class="transactionData.transactionMode === 'rakit_pc' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200'"
                        class="rounded-xl border px-4 py-2 text-sm transition">
                        Rakit PC
                    </button>
                    <span x-show="transactionData.transactionMode === 'rakit_pc' && transactionData.buildName"
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">
                        Nama barang: <span class="font-medium" x-text="transactionData.buildName"></span>
                    </span>
                </div>
            </div>

            {{-- PRODUCT GRID --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <template x-for="product in filteredProducts" :key="product.id">
                    {{-- PRODUCT CARD --}}
                    <div
                        class="flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md">
                        {{-- PRODUCT IMAGE --}}
                        <div class="relative aspect-[4/3] overflow-hidden bg-slate-50/80">
                            <img :src="product.image"
                                class="h-full w-full object-cover object-center"
                                x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                            <div class="absolute inset-x-0 top-0 flex items-start justify-between p-4">
                                <span
                                    class="rounded-full bg-white/90 px-3 py-1 text-xs text-slate-500 ring-1 ring-slate-200/80 backdrop-blur"
                                    x-text="product.category_name"></span>
                                <button type="button" @click.stop="openDetail(product)"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-500 transition hover:bg-white hover:text-slate-900"
                                    title="Lihat Detail" aria-label="Lihat Detail Produk">
                                    <i class="fas fa-info text-sm"></i>
                                </button>
                            </div>
                        </div>
                        {{-- PRODUCT INFO --}}
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="min-h-12 text-[15px] font-semibold leading-6 text-slate-900"
                                x-text="product.name"></h3>
                            {{-- PRODUCT SPECS --}}
                            <div class="mt-3 flex flex-wrap gap-2">
                                <template x-for="spec in getSpecs(product).slice(0, 2)" :key="spec.key + spec.value">
                                    <span
                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">
                                        <span x-text="spec.key"></span>: <span x-text="spec.value"></span>
                                    </span>
                                </template>
                            </div>
                            {{-- PRICE + ACTION --}}
                            <div class="mt-6 flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs text-slate-400">Harga </p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900">Rp <span
                                            x-text="formatNumber(product.base_price)"></span></p>
                                </div>
                                <button @click="handleAddToCart(product)"
                                    class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800">
                                    Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- SIDEBAR CART --}}
        <div class="w-100 shrink-0 sticky top-6">
            <div
                class="flex h-[calc(100vh-60px)] flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                {{-- CART HEADER --}}
                <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Order summary</h2>
                        <p class="mt-1 text-sm text-slate-500">Ringkasan item yang akan diproses.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500"
                        x-text="cart.length + ' item'"></span>
                </div>

                {{-- CART ITEMS --}}
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <template x-if="cart.length === 0">
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                                <i class="fas fa-shopping-bag text-lg"></i>
                            </div>
                            <p class="mt-4 text-sm font-medium text-slate-700">Belum ada item di order.</p>
                            <p class="mt-1 text-sm text-slate-500">Tambahkan produk dari daftar di sebelah kiri.</p>
                        </div>
                    </template>
                    <template x-for="item in cart" :key="item.cartId">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <div class="flex gap-4">
                                <div
                                    class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <img :src="item.image" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start">
                                        <h4 class="pr-2 text-sm font-semibold text-slate-900 line-clamp-2" x-text="item.name">
                                        </h4>
                                        <button @click="removeFromCart(item.cartId)"
                                            class="text-slate-300 transition hover:text-red-500"><i
                                                class="fas fa-times text-xs"></i></button>
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="text-xs text-slate-500" x-text="item.supplierName"></span>
                                        <template x-if="item.isConflict">
                                            <span
                                                class="rounded-full bg-amber-50 px-2 py-1 text-[11px] text-amber-700">Compatibility warning</span>
                                        </template>
                                    </div>
                                    {{-- QTY + LINE TOTAL --}}
                                    <div class="mt-4 flex items-center justify-between">
                                        <div
                                            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-1">
                                            <button @click="updateQty(item.cartId, -1)"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">-</button>
                                            <span class="w-8 text-center text-sm font-medium text-slate-800"
                                                x-text="item.qty"></span>
                                            <button @click="updateQty(item.cartId, 1)"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">+</button>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Rp <span
                                                x-text="formatNumber(item.price * item.qty)"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- CART SUMMARY --}}
                <div class="border-t border-slate-100 bg-white px-6 py-5">
                    <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Subtotal</span>
                            <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Biaya tambahan</span>
                            <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                            <span class="text-sm font-medium text-slate-600">Total bill</span>
                            <span class="text-2xl font-semibold text-slate-900">Rp <span
                                    x-text="formatNumber(finalTotal)"></span></span>
                        </div>
                    </div>
                    <button @click="openModal('modalCheckout')" :disabled="cart.length === 0"
                        class="mt-4 w-full rounded-xl bg-slate-900 py-3.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-30">
                        Finalisasi order
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- MODAL: PRODUCT DETAIL --}}
    <div x-show="detailOpen" x-transition x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 md:p-6" @click.self="closeDetail()">
        <div class="flex max-h-[calc(100vh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-2xl md:max-h-[calc(100vh-3rem)]">
            {{-- MODAL HEADER --}}
            <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4 md:px-6">
                <div>
                    <p class="text-xs text-slate-500">Detail produk</p>
                    <h3 class="mt-1 text-lg font-semibold leading-7 text-slate-900" x-text="detailProduct?.name"></h3>
                </div>
                <button @click="closeDetail()"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            {{-- MODAL BODY --}}
            <div class="overflow-y-auto">
                <div class="grid grid-cols-1 gap-0 lg:grid-cols-[0.95fr,1.05fr]">
                    {{-- IMAGE PREVIEW --}}
                    <div class="border-b border-slate-100 bg-slate-50/70 p-4 md:p-5 lg:border-b-0 lg:border-r">
                        <div class="relative rounded-2xl border border-slate-200 bg-white p-4">
                            <img :src="detailProduct?.image"
                                class="h-64 w-full rounded-2xl bg-slate-50 object-contain p-4 transition-transform duration-300 md:h-72"
                                :class="detailZoom ? 'scale-125 cursor-zoom-out' : 'cursor-zoom-in'"
                                @click="detailZoom = !detailZoom" x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                            {{-- FULLSCREEN ICON --}}
                            <button type="button" @click.stop="openImageViewer()"
                                class="absolute bottom-6 right-6 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 transition hover:bg-slate-50"
                                aria-label="Lihat Gambar Penuh">
                                <i class="fas fa-expand text-[11px]"></i>
                                <span>Perbesar</span>
                            </button>
                        </div>
                    </div>
                    {{-- DETAIL INFO --}}
                    <div class="p-5 md:p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500"
                                x-text="detailProduct?.category_name"></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">
                                Harga mulai Rp <span x-text="formatNumber(detailProduct?.base_price || 0)"></span>
                            </span>
                        </div>

                        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-medium text-slate-700">Ringkasan</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500 line-clamp-4"
                                x-text="detailProduct?.description || 'Belum ada deskripsi produk.'"></p>
                        </div>

                        {{-- SPECIFICATIONS --}}
                        <div class="mt-5">
                            <p class="text-sm font-medium text-slate-700">Spesifikasi</p>
                            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                                <ul class="divide-y divide-slate-200 text-sm text-slate-600">
                                    <template x-for="spec in getSpecs(detailProduct)" :key="spec.key + spec.value + 'detail'">
                                        <li class="grid grid-cols-[120px,1fr] gap-3 bg-white px-4 py-3">
                                            <span class="font-medium text-slate-500" x-text="spec.key"></span>
                                            <span class="text-slate-800 break-words" x-text="spec.value"></span>
                                        </li>
                                    </template>
                                    <template x-if="getSpecs(detailProduct).length === 0">
                                        <li class="bg-white px-4 py-4 text-slate-400">Belum ada spesifikasi produk.</li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                            <div>
                                <p class="text-xs text-slate-400">Harga mulai</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">Rp <span
                                        x-text="formatNumber(detailProduct?.base_price || 0)"></span></p>
                            </div>
                            <button @click="handleAddToCart(detailProduct)"
                                class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                                Tambah ke order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: IMAGE FULLSCREEN --}}
    <div x-show="imageViewerOpen" x-transition x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-6" @click.self="closeImageViewer()">
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
            additionalFees: {
                installation: 0,
                service_labor: 0,
                shipping: 0,
                marketing: 0,
            },
            conflictMessage: '',
            draftBuildName: '',
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
                        pivot_price: Number(s.pivot ? s.pivot.harga_jual_manual : 0)
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
                customerAddress: '',
                type: 'Invoice',
                transactionMode: 'sparepart',
                buildName: '',
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
            get serviceFee() {
                return (Number(this.additionalFees.installation) || 0) +
                    (Number(this.additionalFees.service_labor) || 0) +
                    (Number(this.additionalFees.shipping) || 0) +
                    (Number(this.additionalFees.marketing) || 0);
            },

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
            toggleCompatibility() {
                if (this.transactionData.transactionMode === 'rakit_pc') {
                    this.filterCompatible = true;
                    return;
                }

                this.filterCompatible = !this.filterCompatible;
            },

            setTransactionMode(mode) {
                this.transactionData.transactionMode = mode;
                if (mode === 'rakit_pc') {
                    this.filterCompatible = true;
                    this.draftBuildName = this.transactionData.buildName || '';
                    openModal('modalBuildName');
                    return;
                }

                this.transactionData.buildName = '';
            },

            applyBuildName() {
                const buildName = (this.draftBuildName || '').trim();
                if (!buildName) {
                    alert('Nama barang rakit PC wajib diisi.');
                    return;
                }

                this.transactionData.buildName = buildName;
                closeModal('modalBuildName');
            },

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
                if (this.transactionData.transactionMode === 'rakit_pc' && !this.transactionData.buildName) {
                    this.draftBuildName = '';
                    openModal('modalBuildName');
                    return alert('Isi nama barang untuk transaksi Rakit PC.');
                }

                const payload = {
                    transaction_data: this.transactionData,
                    service_fee: this.serviceFee,
                    additional_fees: this.additionalFees,
                    cart: this.cart.map(i => ({
                        product_id: i.id,
                        name: i.name,
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