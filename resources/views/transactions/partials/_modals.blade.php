{{-- 1. MODAL: PILIH SUPPLIER --}}
<x-modal id="modalSupplier" title="Pilih Sumber Stok" size="md">
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <p class="text-sm font-semibold text-slate-900" x-text="selectedProduct.name"></p>
            <p x-show="selectedProduct.serial_number" x-cloak class="mt-1 font-mono text-xs text-slate-400"
                x-text="selectedProduct.serial_number"></p>
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

                        <!-- SELECT -->
                        <div>
                            <label class="text-sm font-medium mt-3 text-slate-600">Pilih Customer</label>

                            <div class="flex gap-2">
                                <select x-model="selectedCustomerId" @change="selectCustomer"
                                    class="w-full rounded-xl border px-4 py-3 text-sm">

                                    <option value="">-- Customer Baru / Pilih --</option>

                                    <template x-for="cust in customers" :key="cust.id">
                                        <option :value="cust.id" x-text="cust.name"></option>
                                    </template>
                                </select>

                                <button type="button" @click="resetCustomer"
                                    class="px-3 py-2 bg-red-500 text-white rounded-xl text-sm">
                                    Reset
                                </button>
                            </div>
                        </div>

                        <div
                            class="mt-3 flex items-start gap-3 rounded-xl bg-blue-50 border border-blue-200 p-3 text-sm text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mt-0.5 text-blue-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M12 20h.01M12 4a8 8 0 100 16 8 8 0 000-16z" />
                            </svg>

                            <p>
                                Jika customer belum terdaftar, silakan isi data pada form di bawah ini untuk menambahkan
                                customer baru.
                            </p>
                        </div>

                        <!-- NAMA -->
                        <div class="mt-3">
                            <label class="text-sm">Nama</label>
                            <input type="text" x-model="transactionData.customerName"
                                class="w-full border rounded-xl px-4 py-3">
                        </div>

                        <!-- PHONE -->
                        <div class="mt-3">
                            <label class="text-sm">No HP</label>
                            <input type="text" x-model="transactionData.customerPhone"
                                class="w-full border rounded-xl px-4 py-3">
                        </div>

                        <!-- ADDRESS -->
                        <div class="mt-3">
                            <label class="text-sm">Alamat</label>
                            <textarea x-model="transactionData.customerAddress" class="w-full border rounded-xl px-4 py-3"></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <label class="text-sm font-medium text-slate-600">Biaya tambahan - Instalasi</label>
                            <input type="number" min="0" step="0.01"
                                x-model.number="additionalFees.installation" placeholder="0"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <label class="text-sm font-medium text-slate-600">Biaya tambahan - Jasa layanan</label>
                            <input type="number" min="0" step="0.01"
                                x-model.number="additionalFees.service_labor" placeholder="0"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <label class="text-sm font-medium text-slate-600">Diskon transaksi (%)</label>
                            <input type="number" min="0" max="100" step="0.01"
                                x-model.number="additionalFees.discount" placeholder="0"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                            <p class="mt-2 text-xs text-slate-400">Masukkan persentase. Nominal diskon dihitung dari
                                subtotal barang, tanpa biaya instalasi dan jasa.</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <label class="text-sm font-medium text-slate-600">Dokumen transaksi</label>
                            <select x-model="transactionData.type"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                                <option value="Invoice">Invoice</option>
                                <option value="Quotation">Quotation</option>
                                <option value="DO">Delivery Order</option>
                            </select>
                            <p class="mt-2 text-xs text-slate-400"
                                x-text="transactionData.type === 'Invoice'
                                    ? 'Dokumen PDF akan otomatis diunduh setelah transaksi berhasil disimpan.'
                                    : 'Dokumen PDF akan langsung diunduh tanpa menyimpan transaksi.'">
                            </p>
                        </div>

                        
                    </div>
                    <div x-show="transactionData.type === 'Invoice'" x-cloak
                        class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <label class="text-sm font-medium text-slate-600">Metode Pembayaran</label>
                            <select x-model="transactionData.paymentMethod"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400">
                                <option value="midtrans">Online (QRIS/Transfer/Card via Midtrans)</option>
                                <option value="cash">Cash / Tunai</option>
                            </select>
                            <p class="mt-2 text-xs text-slate-400">Pilih metode pembayaran transaksi.</p>
                        </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 lg:p-5">
                    <p class="text-sm font-medium text-slate-700">Ringkasan transaksi</p>
                    <p class="mt-1 text-sm text-slate-500">Pastikan semua detail sudah sesuai sebelum disimpan.</p>

                    {{-- Build mode summary --}}
                    <template x-if="activeBuild">
                        <div class="mt-5 space-y-2">
                            {{-- Info build --}}
                            <div class="rounded-xl bg-blue-50 border border-blue-100 px-3 py-2 mb-3">
                                <p class="text-xs font-semibold text-blue-700"
                                    x-text="'Rakitan: ' + activeBuild.name"></p>
                                <p class="text-xs text-blue-500 mt-0.5"
                                    x-text="Object.values(activeBuild.components).filter(Boolean).length + ' komponen'">
                                </p>
                            </div>
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>Biaya tambahan</span>
                                <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-rose-600">
                                <span>Diskon (<span x-text="additionalFees.discount || 0"></span>%)</span>
                                <span>- Rp <span x-text="formatNumber(discountAmount)"></span></span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                                <span class="text-sm font-semibold text-slate-700">Total Tagihan</span>
                                <span class="text-2xl font-bold text-slate-900">
                                    Rp <span x-text="formatNumber(finalTotal)"></span>
                                </span>
                            </div>
                        </div>
                    </template>

                    {{-- Mode normal summary --}}
                    <template x-if="!activeBuild">
                        <div class="mt-5 space-y-3">
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>Subtotal</span>
                                <span>Rp <span x-text="formatNumber(subtotal)"></span></span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>Biaya tambahan</span>
                                <span>Rp <span x-text="formatNumber(serviceFee)"></span></span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-rose-600">
                                <span>Diskon (<span x-text="additionalFees.discount || 0"></span>%)</span>
                                <span>- Rp <span x-text="formatNumber(discountAmount)"></span></span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                                <span class="text-sm font-medium text-slate-600">Total tagihan</span>
                                <span class="text-2xl font-semibold text-slate-900">
                                    Rp <span x-text="formatNumber(finalTotal)"></span>
                                </span>
                            </div>
                        </div>
                    </template>

                    <button @click="submitOrder()"
                        class="mt-5 w-full rounded-xl bg-slate-900 py-3.5 text-sm font-medium text-white transition hover:bg-slate-800">
                        <span x-text="documentActionLabel"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: KONFIRMASI PINDAH MODE TRANSAKSI --}}
<div x-show="modeSwitchWarningOpen" x-transition x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
            <i class="fas fa-exclamation-triangle text-lg"></i>
        </div>
        <div class="mt-4 text-center">
            <h3 class="text-base font-semibold text-slate-900">Batalkan order saat ini?</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Order
                <span class="font-semibold text-slate-700"
                    x-text="activeBuild?.name || (cart.length + ' item sparepart')"></span>
                akan dikosongkan sebelum pindah ke mode
                <span x-text="pendingTransactionMode === 'rakit_pc' ? 'Rakit PC' : 'Sparepart only'"></span>.
            </p>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-3">
            <button type="button" @click="cancelModeSwitch()"
                class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Tidak
            </button>
            <button type="button" @click="confirmCancelCurrentOrder()"
                class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                Ya, batalkan
            </button>
        </div>
    </div>
</div>

{{-- MODAL: PRODUCT DETAIL --}}
<div x-show="detailOpen" x-transition x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 md:p-6" @click.self="closeDetail()">
    <div
        class="flex max-h-[calc(100vh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-2xl md:max-h-[calc(100vh-3rem)]">
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
                        <span x-show="detailProduct?.serial_number" x-cloak
                            class="rounded-full bg-slate-100 px-3 py-1 font-mono text-xs text-slate-500"
                            x-text="detailProduct?.serial_number"></span>
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
                                <template x-for="spec in getSpecs(detailProduct)"
                                    :key="spec.key + spec.value + 'detail'">
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

{{-- MODAL: BUILD DETAIL --}}
<div x-show="buildDetailOpen" x-transition x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 md:p-6"
    @click.self="closeBuildDetail()">
    <div
        class="flex max-h-[calc(100vh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-2xl md:max-h-[calc(100vh-3rem)]">
        <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4 md:px-6">
            <div>
                <p class="text-xs text-slate-500">Detail build</p>
                <h3 class="mt-1 text-lg font-semibold leading-7 text-slate-900" x-text="detailBuild?.name"></h3>
            </div>
            <button @click="closeBuildDetail()"
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <div class="overflow-y-auto p-5 md:p-6">
            <div class="grid gap-5 lg:grid-cols-[1fr,0.8fr]">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">
                            <span x-text="buildComponentCount(detailBuild)"></span> komponen
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">
                            Margin <span x-text="detailBuild?.margin_pct || 0"></span>%
                        </span>
                    </div>

                    <p x-show="detailBuild?.notes" x-cloak
                        class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-500"
                        x-text="detailBuild?.notes"></p>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-sm font-medium text-slate-700">Rincian komponen</p>
                        </div>
                        <div class="divide-y divide-slate-200">
                            <template x-for="component in buildComponents(detailBuild)"
                                :key="component.id || component.name">
                                <div class="flex items-center justify-between gap-4 bg-white px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-800"
                                            x-text="component.name"></p>
                                        <p class="mt-1 text-xs text-slate-400"
                                            x-text="component.brand || component.category || 'Komponen build'"></p>
                                    </div>
                                    <p class="shrink-0 text-sm font-semibold text-slate-900">
                                        Rp <span x-text="formatNumber(component.price || 0)"></span>
                                    </p>
                                </div>
                            </template>
                            <template x-if="buildComponentCount(detailBuild) === 0">
                                <div class="bg-white px-4 py-6 text-center text-sm text-slate-400">
                                    Belum ada komponen tersimpan.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-700">Ringkasan build</p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Total modal</span>
                            <span x-text="detailBuild?.total_fmt || 'Rp 0'"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600">
                            <span>Margin</span>
                            <span><span x-text="detailBuild?.margin_pct || 0"></span>%</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                            <span class="text-sm font-semibold text-slate-700">Harga jual set</span>
                            <span class="text-xl font-bold text-slate-900"
                                x-text="detailBuild?.harga_jual_fmt || 'Rp 0'"></span>
                        </div>
                    </div>

                    <button @click="applyBuild(detailBuild)"
                        class="mt-5 w-full rounded-xl bg-slate-900 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                        Muat ke order
                    </button>

                    <button @click="deleteBuild(detailBuild?.id)" :disabled="!detailBuild?.id"
                        class="mt-2 w-full rounded-xl bg-red-50 py-3 text-sm font-medium text-red-600 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-30 border border-red-200">
                        Hapus Build
                    </button>
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
