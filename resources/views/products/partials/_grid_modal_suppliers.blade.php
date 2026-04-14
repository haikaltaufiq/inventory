<x-modal id="modal-product-suppliers" title="Kelola Supplier" size="xl">
    <template x-if="activeSupplierRow()">
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800"
                        x-text="activeSupplierRow()?.name || 'Produk'"></h3>
                    <p class="mt-1 text-xs text-slate-500">Pilih supplier lama dari dropdown, atau ganti ke mode
                        supplier baru jika supplier belum ada di database.</p>
                </div>
                <button type="button" @click="addSupplier(activeSupplierRow())"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">Tambah
                    Supplier</button>
            </div>
            <div x-show="hasDuplicateSupplierCondition(activeSupplierRow())"
                class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                Kombinasi supplier dan kondisi harus unik per produk. Jika stoknya sama supplier dan kondisi yang
                sama, gabungkan ke satu baris agar harga tidak saling menimpa.
            </div>
            <div class="space-y-4">
                <template
                    x-for="(supplierRow, supplierIndex) in activeSupplierRow() ? activeSupplierRow().suppliers : []"
                    :key="`modal-supplier-${supplierIndex}`">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-slate-700"
                                    x-text="supplierCardTitle(supplierRow, supplierIndex)"></span>
                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-[10px] font-medium text-slate-500 ring-1 ring-slate-200"
                                    x-text="supplierModeLabel(supplierRow)"></span>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-medium"
                                    :class="conditionMeta(supplierRow.condition)"
                                    x-text="supplierRow.condition || '-'"></span>
                            </div>
                            <button type="button" @click="removeSupplier(activeSupplierRow(), supplierIndex)"
                                class="text-sm text-red-500 transition hover:text-red-600">Hapus</button>
                        </div>
                        <div class="grid gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <label
                                            class="block text-xs font-medium uppercase tracking-wider text-slate-400">Sumber
                                            Supplier</label>
                                        <p class="mt-1 text-xs text-slate-500">Dropdown tetap untuk supplier lama,
                                            tapi bisa langsung buat supplier baru dari sini.</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button"
                                            @click="setSupplierMode(activeSupplierRow(), supplierRow, 'existing')"
                                            :class="supplierRow.mode === 'existing' ? 'bg-slate-900 text-white' :
                                                'bg-slate-100 text-slate-600'"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Supplier
                                            Lama</button>
                                        <button type="button"
                                            @click="setSupplierMode(activeSupplierRow(), supplierRow, 'new')"
                                            :class="supplierRow.mode === 'new' ? 'bg-slate-900 text-white' :
                                                'bg-slate-100 text-slate-600'"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Supplier
                                            Baru</button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="supplierRow.mode === 'existing'" x-cloak>
                                <label
                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Pilih
                                    Supplier Lama</label>
                                <select :key="`supplier-select-${supplierIndex}-${supplierRow.supplier_id || ''}`"
                                    x-model="supplierRow.supplier_id"
                                    @change="onSupplierSelectChange(activeSupplierRow(), supplierRow)"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                    <option value="" :selected="!supplierRow.supplier_id">Pilih supplier
                                    </option>
                                    <template x-for="supplier in suppliers"
                                        :key="`modal-${supplierIndex}-${supplier.id}`">
                                        <option :value="String(supplier.id)"
                                            :selected="String(supplierRow.supplier_id || '') === String(supplier.id)"
                                            x-text="supplier.name"></option>
                                    </template>
                                    <option value="__new__">+ Input supplier baru</option>
                                </select>
                            </div>

                            <div x-show="supplierRow.mode === 'new'" x-cloak class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Nama
                                        Supplier Baru</label>
                                    <input x-model="supplierRow.new_supplier_name"
                                        @input="markDirty(activeSupplierRow())" type="text"
                                        placeholder="Contoh: CV Sumber Jaya"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Alamat
                                        Supplier Baru</label>
                                    <input x-model="supplierRow.new_supplier_address"
                                        @input="markDirty(activeSupplierRow())" type="text"
                                        placeholder="Alamat supplier"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Pemodal</label>
                                    <select
                                        :key="`investor-select-${supplierIndex}-${supplierRow.pemodal_user_id || ''}`"
                                        x-model="supplierRow.pemodal_user_id"
                                        @change="markDirty(activeSupplierRow())"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                        <option value="" :selected="!supplierRow.pemodal_user_id">Pilih
                                            pemodal</option>
                                        <template x-for="user in users"
                                            :key="`modal-user-${supplierIndex}-${user.id}`">
                                            <option :value="String(user.id)"
                                                :selected="String(supplierRow.pemodal_user_id || '') === String(user.id)"
                                                x-text="user.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Kondisi</label>
                                    <select x-model="supplierRow.condition"
                                        @change="markDirty(activeSupplierRow())"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                        <template x-for="condition in conditionOptions"
                                            :key="`modal-cond-${supplierIndex}-${condition}`">
                                            <option :value="condition" x-text="condition"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Stok</label>
                                    <input x-model="supplierRow.stock" @input="markDirty(activeSupplierRow())"
                                        type="number" min="0" placeholder="Stok"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Harga
                                        Modal</label>
                                    <input x-model="supplierRow.harga_beli"
                                        @input="markDirty(activeSupplierRow())" type="number" min="0"
                                        placeholder="Harga beli"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Harga
                                        jual</label>
                                    <input x-model="supplierRow.harga_jual"
                                        @input="markDirty(activeSupplierRow())" type="number" min="0"
                                        placeholder="Harga jual"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full"
                                        required>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Garansi (Warranty)</label>
                                    <input x-model="supplierRow.warranty_detail"
                                        @input="markDirty(activeSupplierRow())" type="text"
                                        placeholder="Detail garansi supplier"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <button type="button" onclick="closeModal('modal-product-suppliers')"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" onclick="closeModal('modal-product-suppliers')"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                    OK
                </button>
            </div>
        </div>
    </template>
</x-modal>
