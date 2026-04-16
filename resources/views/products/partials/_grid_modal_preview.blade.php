<x-modal id="modal-product-preview" title="Detail Produk" size="xl">
    <template x-if="activePreviewRow()">
        <div class="space-y-5">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                    <p class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Produk</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900"
                        x-text="activePreviewRow()?.name || 'Produk'"></h3>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                        <span class="font-mono"
                            x-text="activePreviewRow()?.id ? `#PRD-${String(activePreviewRow().id).padStart(4, '0')}` : '#DRAFT'"></span>
                        <span class="rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200"
                            x-text="categoryName(activePreviewRow())"></span>
                        <span class="rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200"
                            x-text="activePreviewRow()?.brand || 'Tanpa brand'"></span>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-medium"
                            :class="statusMeta(activePreviewRow()).className"
                            x-text="statusMeta(activePreviewRow()).label"></span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600"
                        x-text="activePreviewRow()?.description || 'Belum ada deskripsi produk.'"></p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Ringkasan</p>
                    <div class="mt-3 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Total stok</span>
                            <span class="font-semibold text-slate-900 tabular-nums"
                                x-text="formatNumber(rowStock(activePreviewRow()))"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Supplier aktif</span>
                            <span class="font-semibold text-slate-900 tabular-nums"
                                x-text="formatNumber(activeSupplierEntries(activePreviewRow()).length)"></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Pemodal</span>
                            <span class="font-medium text-slate-900 text-right"
                                x-text="investorSummary(activePreviewRow())"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Letak barang</span>
                            <span class="font-medium text-slate-900 text-right"
                                x-text="activePreviewRow()?.letak_barang || '-'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h4 class="text-sm font-semibold text-slate-800">Harga per Supplier</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-[11px] font-medium uppercase tracking-[0.14em]">Supplier
                                </th>
                                <th class="px-4 py-3 text-[11px] font-medium uppercase tracking-[0.14em]">Pemodal
                                </th>
                                <th class="px-4 py-3 text-[11px] font-medium uppercase tracking-[0.14em]">Kondisi
                                </th>
                                <th class="px-4 py-3 text-[11px] font-medium uppercase tracking-[0.14em]">Garansi
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-[11px] font-medium uppercase tracking-[0.14em]">
                                    Stok</th>
                                <th
                                    class="px-4 py-3 text-right text-[11px] font-medium uppercase tracking-[0.14em]">
                                    Harga Modal</th>
                                <th
                                    class="px-4 py-3 text-right text-[11px] font-medium uppercase tracking-[0.14em]">
                                    Harga Jual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="supplierEntry in activeSupplierEntries(activePreviewRow())"
                                :key="`preview-supplier-${supplierEntry.index}`">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-800" x-text="supplierEntry.name"></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"
                                        x-text="supplierEntry.pemodalName || '-'"></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-medium"
                                            :class="conditionMeta(supplierEntry.condition)"
                                            x-text="supplierEntry.condition || '-'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700"
                                        x-text="supplierEntry.warranty_detail || '-'"></td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-900 tabular-nums"
                                        x-text="formatNumber(supplierEntry.stock)"></td>
                                    <td class="px-4 py-3 text-right text-slate-700 tabular-nums"
                                        x-text="`Rp ${formatCurrency(supplierEntry.harga_beli)}`"></td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-900 tabular-nums"
                                        x-text="`Rp ${formatCurrency(supplierEntry.harga_jual)}`"></td>
                                </tr>
                            </template>
                            <tr x-show="activeSupplierEntries(activePreviewRow()).length === 0">
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada
                                    supplier aktif untuk produk ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-800">Spesifikasi Utama</h4>
                    <div class="mt-3 space-y-2 text-sm">
                        <template x-for="field in templateFields(activePreviewRow())"
                            :key="`preview-field-${field.key}`">
                            <div
                                class="flex items-start justify-between gap-4 border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                                <span class="text-slate-500" x-text="field.label"></span>
                                <span class="text-right font-medium text-slate-900"
                                    x-text="activePreviewRow()?.specs?.[field.key]?.value || '-'"></span>
                            </div>
                        </template>
                        <div x-show="templateFields(activePreviewRow()).length === 0"
                            class="text-sm text-slate-400">
                            Belum ada spesifikasi utama.
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-800">Spesifikasi Tambahan</h4>
                    <div class="mt-3 space-y-2 text-sm">
                        <template x-for="(spec, specIndex) in activePreviewRow()?.additional_specs || []"
                            :key="`preview-extra-${specIndex}`">
                            <div
                                class="flex items-start justify-between gap-4 border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                                <span class="text-slate-500" x-text="spec.key || '-'"></span>
                                <span class="text-right font-medium text-slate-900"
                                    x-text="spec.value || '-'"></span>
                            </div>
                        </template>
                        <div x-show="(activePreviewRow()?.additional_specs || []).length === 0"
                            class="text-sm text-slate-400">
                            Belum ada spesifikasi tambahan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</x-modal>
