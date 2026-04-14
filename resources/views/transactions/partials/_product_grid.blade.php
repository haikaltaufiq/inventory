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
