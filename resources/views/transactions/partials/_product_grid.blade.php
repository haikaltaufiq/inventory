{{-- BANNER BUILD MODE (muncul saat ada aktiveBuild) --}}
<div x-show="activeBuild" x-transition x-cloak
    class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3 min-w-0">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
            <i class="fas fa-microchip text-sm"></i>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-blue-900" x-text="'Build: ' + (activeBuild?.name ?? '')"></p>
            <p class="text-xs text-blue-600 mt-0.5">
                Hanya menampilkan komponen dalam rakitan ini ·
                Margin <span x-text="buildMarginPct + '%'"></span>
            </p>
        </div>
    </div>
    <button @click="exitBuildMode()"
        class="flex-shrink-0 flex items-center gap-2 rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100 transition">
        <i class="fas fa-times text-xs"></i>
        Keluar Mode Build
    </button>
</div>

{{-- PRODUCT GRID --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <template x-for="product in filteredProducts" :key="product.id">
        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md">
            {{-- PRODUCT IMAGE --}}
            <div class="relative aspect-[4/3] overflow-hidden bg-slate-50/80">
                <img :src="product.image"
                    class="h-full w-full object-cover object-center"
                    x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                <div class="absolute inset-x-0 top-0 flex items-start justify-between p-4">
                    <span class="rounded-full bg-white/90 px-3 py-1 text-xs text-slate-500 ring-1 ring-slate-200/80 backdrop-blur"
                        x-text="product.category_name"></span>
                    <button type="button" @click.stop="openDetail(product)"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-500 transition hover:bg-white hover:text-slate-900">
                        <i class="fas fa-info text-sm"></i>
                    </button>
                </div>
            </div>
            {{-- PRODUCT INFO --}}
            <div class="flex flex-1 flex-col p-5">
                <h3 class="min-h-12 text-[15px] font-semibold leading-6 text-slate-900"
                    x-text="product.name"></h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-for="spec in getSpecs(product).slice(0, 2)" :key="spec.key + spec.value">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">
                            <span x-text="spec.key"></span>: <span x-text="spec.value"></span>
                        </span>
                    </template>
                </div>
                {{-- PRICE — build mode: tampilkan harga modal dari komponen build --}}
                <div class="mt-6 flex items-end justify-between gap-4">
                    <div>
                        {{-- Label berubah sesuai mode --}}
                        <p class="text-xs text-slate-400"
                            x-text="activeBuild ? 'Harga Modal' : 'Harga'"></p>

                        {{-- Build mode: pakai harga modal dari komponen --}}
                        <template x-if="activeBuild">
                            <p class="mt-1 text-lg font-semibold text-slate-900">
                                Rp <span x-text="formatNumber(
                                    Object.values(activeBuild.components)
                                        .filter(c => c && c.id === product.id)
                                        .map(c => c.price)[0] ?? product.base_price
                                )"></span>
                            </p>
                        </template>

                        {{-- Normal mode: pakai base_price --}}
                        <template x-if="!activeBuild">
                            <p class="mt-1 text-lg font-semibold text-slate-900">
                                Rp <span x-text="formatNumber(product.base_price)"></span>
                            </p>
                        </template>
                    </div>
                    <button @click="handleAddToCart(product)"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800">
                        Tambah
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Empty state saat build mode tapi cart sudah penuh semua --}}
    <template x-if="filteredProducts.length === 0 && activeBuild">
        <div class="col-span-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-12 text-center">
            <i class="fas fa-check-circle text-2xl text-emerald-400 mb-3"></i>
            <p class="text-sm font-medium text-slate-600">Semua komponen build sudah tersedia.</p>
            <p class="text-xs text-slate-400 mt-1">Lanjutkan ke finalisasi order.</p>
        </div>
    </template>
</div>
