<x-modal id="modal-product-detail" title="Detail Produk" size="xl">
    <template x-if="activeDetailRow()">
        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Brand</label>
                    <input x-model="activeDetailRow().brand" @input="markDirty(activeDetailRow())" type="text"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Foto
                        Produk</label>
                    <button type="button" @click="pickImage(activeDetailRow())"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Pilih / Ganti Gambar
                    </button>
                    <p class="mt-1 text-xs text-slate-500"
                        x-text="activeDetailRow()?._imageName ? `File baru: ${activeDetailRow()._imageName}` : (activeDetailRow()?.image_url ? 'Gambar lama tersimpan.' : 'Belum ada gambar.')">
                    </p>
                    <a x-show="activeDetailRow()?.image_url" x-cloak :href="activeDetailRow()?.image_url || '#'"
                        target="_blank" rel="noreferrer"
                        class="mt-2 inline-flex text-xs font-medium text-sky-600 transition hover:text-sky-700">
                        Lihat gambar saat ini
                    </a>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Letak
                        Barang</label>
                    <input x-model="activeDetailRow().letak_barang" @input="markDirty(activeDetailRow())"
                        type="text"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                </div>
                <div class="md:col-span-2">
                    <label
                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Deskripsi</label>
                    <textarea x-model="activeDetailRow().description" @input="markDirty(activeDetailRow())" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400"></textarea>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Spesifikasi Utama</h3>
                        <p class="mt-1 text-xs text-slate-500">Template mengikuti kategori produk.</p>
                    </div>
                </div>
                <div x-show="activeDetailRow() && templateFields(activeDetailRow()).length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                    Pilih kategori di grid untuk memunculkan field spesifikasi.</div>
                <div x-show="activeDetailRow() && templateFields(activeDetailRow()).length > 0"
                    class="grid gap-4 md:grid-cols-2">
                    <template x-for="field in activeDetailRow() ? templateFields(activeDetailRow()) : []"
                        :key="`detail-${field.key}`">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-sm font-medium text-slate-700" x-text="field.label"></label>
                                <span class="rounded-full px-2 py-1 text-[10px]"
                                    :class="field.required ? 'bg-slate-900 text-white' : 'bg-white text-slate-500'"
                                    x-text="field.required ? 'Required' : 'Optional'"></span>
                            </div>
                            <div class="mt-3 space-y-3">
                                <div class="flex gap-2">
                                    <button type="button"
                                        @click="setSpecMode(activeDetailRow(), field.key, 'existing')"
                                        :class="activeDetailRow()?.specs[field.key]?.mode === 'existing' ?
                                            'bg-slate-900 text-white' :
                                            'bg-white text-slate-600 ring-1 ring-slate-200'"
                                        class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Pilih
                                        Referensi</button>
                                    <button type="button"
                                        @click="setSpecMode(activeDetailRow(), field.key, 'new')"
                                        :class="activeDetailRow()?.specs[field.key]?.mode === 'new' ?
                                            'bg-slate-900 text-white' :
                                            'bg-white text-slate-600 ring-1 ring-slate-200'"
                                        class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Input
                                        Baru</button>
                                </div>

                                <div x-show="activeDetailRow()?.specs[field.key]?.mode !== 'new'" x-cloak>
                                    <label class="mb-1 block text-[11px] font-medium text-slate-400">Gunakan value
                                        yang sudah ada</label>
                                    <select
                                        :key="`spec-select-${activeDetailRow()?.client_key || 'row'}-${field.key}-${activeDetailRow()?.specs?.[field.key]?.value || ''}`"
                                        x-model="activeDetailRow().specs[field.key].value"
                                        @change="markDirty(activeDetailRow())"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-slate-400">
                                        <option value=""
                                            :selected="!(activeDetailRow()?.specs?.[field.key]?.value || '')">Pilih
                                            value</option>
                                        <template
                                            x-for="option in activeDetailRow() ? specSelectOptions(activeDetailRow(), field.key) : []"
                                            :key="`detail-${field.key}-${option}`">
                                            <option :value="option"
                                                :selected="String(activeDetailRow()?.specs?.[field.key]?.value || '') ===
                                                    String(option)"
                                                x-text="option"></option>
                                        </template>
                                    </select>
                                </div>

                                <div x-show="activeDetailRow()?.specs[field.key]?.mode === 'new'" x-cloak>
                                    <label class="mb-1 block text-[11px] font-medium text-slate-400">Input value
                                        baru</label>
                                    <input :value="activeDetailRow()?.specs[field.key]?.value || ''"
                                        @input="updateSpec(activeDetailRow(), field.key, $event.target.value)"
                                        @blur="normalizeSpecEntry(activeDetailRow(), field.key)"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-slate-400"
                                        :placeholder="field.placeholder || 'Masukkan value baru'">
                                    <p class="mt-1 text-[11px] text-slate-400">Jika penulisannya ternyata sama
                                        dengan referensi lama, sistem akan otomatis menyamakan formatnya.</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Spesifikasi Tambahan</h3>
                        <p class="mt-1 text-xs text-slate-500">Atribut non-template tetap bisa disimpan.</p>
                    </div>
                    <button type="button" @click="addExtraSpec(activeDetailRow())"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">Tambah</button>
                </div>
                <div class="space-y-3">
                    <template
                        x-for="(extraSpec, extraIndex) in activeDetailRow() ? activeDetailRow().additional_specs : []"
                        :key="`modal-extra-${extraIndex}`">
                        <div
                            class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[0.9fr,1.1fr,auto]">
                            <input x-model="extraSpec.key" @input="markDirty(activeDetailRow())" type="text"
                                placeholder="Key"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                            <input x-model="extraSpec.value" @input="markDirty(activeDetailRow())" type="text"
                                placeholder="Value"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                            <button type="button" @click="removeExtraSpec(activeDetailRow(), extraIndex)"
                                class="rounded-xl bg-white px-3 py-2.5 text-sm text-red-500 ring-1 ring-slate-200 transition hover:bg-red-50">Hapus</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <button type="button" onclick="closeModal('modal-product-detail')"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" onclick="closeModal('modal-product-detail')"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                    OK
                </button>
            </div>
        </div>
    </template>
</x-modal>
