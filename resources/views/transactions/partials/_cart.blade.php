{{-- SIDEBAR CART --}}
<div class="w-full shrink-0 md:sticky md:top-5 md:w-[17.5rem] lg:w-[20rem] xl:w-[23rem] 2xl:w-[25rem]">
    <div
        class="flex max-h-none flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm md:h-[calc(100vh-3.5rem)]">
        {{-- CART HEADER --}}
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-4 xl:px-5">
            <div class="min-w-0">
                <h2 class="text-[15px] font-semibold text-slate-900 xl:text-base">Order summary</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500 xl:text-sm">Ringkasan item yang akan diproses.</p>
            </div>
            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500"
                x-text="cart.length + ' item'"></span>
        </div>

        {{-- CART ITEMS --}}
        <div class="min-h-56 flex-1 space-y-3 overflow-y-auto px-4 py-4 xl:px-5">
            <template x-if="cart.length === 0">
                <div class="flex min-h-48 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                        <i class="fas fa-shopping-bag text-base"></i>
                    </div>
                    <p class="mt-4 text-sm font-medium text-slate-700">Belum ada item di order.</p>
                    <p class="mt-1 max-w-52 text-xs leading-5 text-slate-500 xl:text-sm">Tambahkan produk dari daftar produk.</p>
                </div>
            </template>
            <template x-for="item in cart" :key="item.cartId">
                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3 xl:p-4">
                    <div class="flex gap-3">
                        <div
                            class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white xl:h-16 xl:w-16">
                            <img :src="item.image" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <h4 class="pr-1 text-xs font-semibold leading-5 text-slate-900 line-clamp-2 xl:text-sm" x-text="item.name">
                                </h4>
                                <button @click="removeFromCart(item.cartId)"
                                    class="text-slate-300 transition hover:text-red-500"><i
                                        class="fas fa-times text-xs"></i></button>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="truncate text-[11px] text-slate-500 xl:text-xs" x-text="item.supplierName"></span>
                                <template x-if="item.isConflict">
                                    <span
                                        class="rounded-full bg-amber-50 px-2 py-1 text-[11px] text-amber-700">Compatibility warning</span>
                                </template>
                            </div>
                            {{-- QTY + LINE TOTAL --}}
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <div
                                    class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-1">
                                    <button @click="updateQty(item.cartId, -1)"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">-</button>
                                    <span class="w-8 text-center text-sm font-medium text-slate-800"
                                        x-text="item.qty"></span>
                                    <button @click="updateQty(item.cartId, 1)"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">+</button>
                                </div>
                                <p class="text-xs font-semibold text-slate-900 tabular-nums xl:text-sm">Rp <span
                                        x-text="formatNumber(item.price * item.qty)"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- CART SUMMARY --}}
        <div class="border-t border-slate-100 bg-white px-4 py-4 xl:px-5">
            <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 xl:p-4">

                {{-- Build mode: tampilkan breakdown modal + margin --}}
                <template x-if="activeBuild">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500 xl:text-sm">
                            <span>Total Modal</span>
                            <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-emerald-600 xl:text-sm">
                            <span>Margin (<span x-text="buildMarginPct"></span>%)</span>
                            <span>+ Rp <span x-text="formatNumber(buildMarginAmount)"></span></span>
                        </div>
                        <template x-if="serviceFee > 0">
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>Biaya tambahan</span>
                                <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                            </div>
                        </template>
                        <template x-if="discountAmount > 0">
                            <div class="flex items-center justify-between text-sm text-rose-600">
                                <span>Diskon (<span x-text="additionalFees.discount || 0"></span>%)</span>
                                <span>- Rp <span x-text="formatNumber(discountAmount)"></span></span>
                            </div>
                        </template>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                            <span class="text-xs font-semibold text-slate-700 xl:text-sm">Harga Jual Set</span>
                            <span class="text-right text-lg font-bold text-slate-900 tabular-nums xl:text-xl">
                                Rp <span x-text="formatNumber(finalTotal)"></span>
                            </span>
                        </div>
                    </div>
                </template>

                {{-- Mode normal --}}
                <template x-if="!activeBuild">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500 xl:text-sm">
                            <span>Subtotal</span>
                            <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500 xl:text-sm">
                            <span>Biaya tambahan</span>
                            <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-rose-600 xl:text-sm">
                            <span>Diskon (<span x-text="additionalFees.discount || 0"></span>%)</span>
                            <span>- Rp <span x-text="formatNumber(discountAmount)"></span></span>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                            <span class="text-xs font-medium text-slate-600 xl:text-sm">Total bill</span>
                            <span class="text-right text-xl font-semibold text-slate-900 tabular-nums xl:text-2xl">
                                Rp <span x-text="formatNumber(finalTotal)"></span>
                            </span>
                        </div>
                    </div>
                </template>

            </div>
            
            <button @click="openModal('modalCheckout')" :disabled="cart.length === 0"
                class="mt-4 w-full rounded-xl bg-slate-900 py-3 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-30 xl:py-3.5">
                Finalisasi order
            </button>
        </div>
    </div>
</div>
