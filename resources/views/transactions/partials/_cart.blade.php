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

                {{-- Build mode: tampilkan breakdown modal + margin --}}
                <template x-if="activeBuild">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Total Modal</span>
                            <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600">
                            <span>Margin (<span x-text="buildMarginPct"></span>%)</span>
                            <span>+ Rp <span x-text="formatNumber(buildMarginAmount)"></span></span>
                        </div>
                        <template x-if="serviceFee > 0">
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>Biaya tambahan</span>
                                <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                            </div>
                        </template>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                            <span class="text-sm font-semibold text-slate-700">Harga Jual Set</span>
                            <span class="text-xl font-bold text-slate-900">
                                Rp <span x-text="formatNumber(finalTotal)"></span>
                            </span>
                        </div>
                    </div>
                </template>

                {{-- Mode normal --}}
                <template x-if="!activeBuild">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Subtotal</span>
                            <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Biaya tambahan</span>
                            <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                            <span class="text-sm font-medium text-slate-600">Total bill</span>
                            <span class="text-2xl font-semibold text-slate-900">
                                Rp <span x-text="formatNumber(finalTotal)"></span>
                            </span>
                        </div>
                    </div>
                </template>

            </div>
            
            <button @click="openModal('modalCheckout')" :disabled="cart.length === 0"
                class="mt-4 w-full rounded-xl bg-slate-900 py-3.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-30">
                Finalisasi order
            </button>
        </div>
    </div>
</div>
