{{-- BANNER BUILD MODE (muncul saat ada aktiveBuild) --}}
<div x-show="activeBuild" x-transition x-cloak
    class="mb-4 flex flex-col gap-4 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
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
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:gap-4 2xl:grid-cols-4">
    <template x-for="product in filteredProducts" :key="product.id">
        <div
            class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md">
            {{-- PRODUCT IMAGE --}}
            <div class="relative aspect-[4/3] overflow-hidden bg-slate-50/80 md:aspect-square lg:aspect-[4/3]">
                <img :src="product.image" class="h-full w-full object-cover object-center"
                    x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-2.5 xl:p-3">
                    <span
                        class="max-w-[72%] truncate rounded-full bg-white/90 px-2.5 py-1 text-[10px] text-slate-500 ring-1 ring-slate-200/80 backdrop-blur xl:text-xs"
                        x-text="product.category_name"></span>
                    <button type="button" @click.stop="openDetail(product)"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-500 transition hover:bg-white hover:text-slate-900 xl:h-9 xl:w-9">
                        <i class="fas fa-info text-xs xl:text-sm"></i>
                    </button>
                </div>
            </div>
            {{-- PRODUCT INFO --}}
            <div class="flex min-w-0 flex-1 flex-col p-3 xl:p-4">
                <h3 class="line-clamp-2 min-h-10 break-words text-[12px] font-semibold leading-5 text-slate-900 lg:text-[13px] xl:min-h-12 xl:text-[15px] xl:leading-6"
                    x-text="product.name"></h3>
                <p x-show="product.serial_number" x-cloak
                    class="mt-1 truncate font-mono text-[10px] text-slate-400 xl:text-xs"
                    x-text="product.serial_number"></p>
                <div class="mt-2 flex flex-wrap gap-1.5 xl:mt-3 xl:gap-2">
                    <template x-for="spec in getSpecs(product).slice(0, 2)" :key="spec.key + spec.value">
                        <span
                            class="max-w-full truncate rounded-full bg-slate-100 px-2 py-1 text-[10px] leading-4 text-slate-500 xl:px-2.5 xl:text-xs">
                            <span x-text="spec.key"></span>: <span x-text="spec.value"></span>
                        </span>
                    </template>
                </div>
                {{-- PRICE — build mode: tampilkan harga modal dari komponen build --}}
                <div class="mt-auto flex items-end justify-between gap-2 pt-4 xl:gap-3 xl:pt-5">
                    <div class="min-w-0">
                        {{-- Label berubah sesuai mode --}}
                        <p class="text-[10px] text-slate-400 xl:text-xs" x-text="activeBuild ? 'Harga Modal' : 'Harga'">
                        </p>

                        {{-- Build mode: pakai harga modal dari komponen --}}
                        <template x-if="activeBuild">
                            <p class="mt-1 truncate text-sm font-semibold text-slate-900 lg:text-base xl:text-lg">
                                Rp <span
                                    x-text="formatNumber(
                                    Object.values(activeBuild.components)
                                        .filter(c => c && c.id === product.id)
                                        .map(c => c.price)[0] ?? product.base_price
                                )"></span>
                            </p>
                        </template>

                        {{-- Normal mode: pakai base_price --}}
                        <template x-if="!activeBuild">
                            <p class="mt-1 truncate text-sm font-semibold text-slate-900 lg:text-base xl:text-lg">
                                Rp <span x-text="formatNumber(product.base_price)"></span>
                            </p>
                        </template>
                    </div>
                    <button @click="handleAddToCart(product)"
                        class="inline-flex h-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 px-3 text-xs font-medium text-white transition hover:bg-slate-800 xl:h-10 xl:px-4 xl:text-sm">
                        +
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Empty state saat build mode tapi cart sudah penuh semua --}}
    <template x-if="filteredProducts.length === 0 && activeBuild">
        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-12 text-center">
            <i class="fas fa-check-circle text-2xl text-emerald-400 mb-3"></i>
            <p class="text-sm font-medium text-slate-600">Semua komponen build sudah tersedia.</p>
            <p class="text-xs text-slate-400 mt-1">Lanjutkan ke finalisasi order.</p>
        </div>
    </template>
</div>
