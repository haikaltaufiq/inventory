{{-- PRODUCT GRID --}}
<div x-show="transactionData.transactionMode === 'sparepart'" x-cloak
    class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:gap-4 2xl:grid-cols-4">
    <template x-for="product in filteredProducts" :key="product.id">
        <div
            class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md">
            <div class="relative aspect-4/3 overflow-hidden bg-slate-50/80 md:aspect-square lg:aspect-[4/3]">
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
            <div class="flex min-w-0 flex-1 flex-col p-3 xl:p-4">
                <h3 class="line-clamp-2 min-h-10 wrap-break-word text-[12px] font-semibold leading-5 text-slate-900 lg:text-[13px] xl:min-h-12 xl:text-[15px] xl:leading-6"
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
                <div class="mt-auto flex items-end justify-between gap-2 pt-4 xl:gap-3 xl:pt-5">
                    <div class="min-w-0">
                        <p class="text-[10px] text-slate-400 xl:text-xs">Harga</p>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-900 lg:text-base xl:text-lg">
                            Rp <span x-text="formatNumber(product.base_price)"></span>
                        </p>
                    </div>
                    <button @click="handleAddToCart(product)"
                        class="inline-flex h-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 px-3 text-xs font-medium text-white transition hover:bg-slate-800 xl:h-10 xl:px-4 xl:text-sm">
                        +
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="filteredProducts.length === 0">
        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-12 text-center">
            <i class="fas fa-search text-2xl text-slate-300 mb-3"></i>
            <p class="text-sm font-medium text-slate-600">Produk tidak ditemukan.</p>
            <p class="text-xs text-slate-400 mt-1">Coba ubah pencarian atau kategori.</p>
        </div>
    </template>
</div>

{{-- BUILD GRID --}}
<div x-show="transactionData.transactionMode === 'rakit_pc'" x-cloak
    class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:gap-4 2xl:grid-cols-4">
    <template x-for="build in filteredBuilds" :key="build.id">
        <div
            class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md">
            <div class="relative aspect-[4/3] overflow-hidden bg-slate-50/80 md:aspect-square lg:aspect-[4/3]">
                <img :src="buildCoverImage(build)" class="h-full w-full object-cover object-center"
                    x-on:error="$el.src=@js(asset('assets/no-image.svg'))">
                <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-2.5 xl:p-3">
                    <span
                        class="max-w-[72%] truncate rounded-full bg-white/90 px-2.5 py-1 text-[10px] text-slate-500 ring-1 ring-slate-200/80 backdrop-blur xl:text-xs"
                        x-text="build.status === 'draft' ? 'Draft' : build.status === 'deal' ? 'Deal' : 'Cancelled'"></span>
                    <button type="button" @click.stop="openBuildDetail(build)"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-500 transition hover:bg-white hover:text-slate-900 xl:h-9 xl:w-9">
                        <i class="fas fa-info text-xs xl:text-sm"></i>
                    </button>
                </div>
            </div>
            <div class="flex min-w-0 flex-1 flex-col p-3 xl:p-4">
                <h3 class="line-clamp-2 min-h-10 break-words text-[12px] font-semibold leading-5 text-slate-900 lg:text-[13px] xl:min-h-12 xl:text-[15px] xl:leading-6"
                    x-text="build.name"></h3>
                <p class="mt-1 truncate text-[10px] text-slate-400 xl:text-xs"
                    x-text="build.created_at + ' - ' + (build.created_by || 'Unknown')"></p>
                <div class="mt-2 flex flex-wrap gap-1.5 xl:mt-3 xl:gap-2">
                    <span
                        class="max-w-full truncate rounded-full bg-slate-100 px-2 py-1 text-[10px] leading-4 text-slate-500 xl:px-2.5 xl:text-xs">
                        <span x-text="buildComponentCount(build)"></span> komponen
                    </span>
                    <span
                        class="max-w-full truncate rounded-full bg-slate-100 px-2 py-1 text-[10px] leading-4 text-slate-500 xl:px-2.5 xl:text-xs">
                        Margin <span x-text="build.margin_pct"></span>%
                    </span>
                </div>
                <div class="mt-auto flex items-end justify-between gap-2 pt-4 xl:gap-3 xl:pt-5">
                    <div class="min-w-0">
                        <p class="text-[10px] text-slate-400 xl:text-xs">Harga jual set</p>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-900 lg:text-base xl:text-lg"
                            x-text="build.harga_jual_fmt"></p>
                    </div>
                    <button @click="applyBuild(build)" :disabled="build.status === 'cancelled'"
                        class="inline-flex h-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 px-3 text-xs font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-30 xl:h-10 xl:px-4 xl:text-sm">
                        +
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="savedBuildsLoaded && filteredBuilds.length === 0">
        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-12 text-center">
            <i class="fas fa-microchip text-2xl text-slate-300 mb-3"></i>
            <p class="text-sm font-medium text-slate-600">Build tidak ditemukan.</p>
            <p class="text-xs text-slate-400 mt-1">Coba ubah pencarian atau buat build dari menu Simulasi.</p>
        </div>
    </template>
</div>
