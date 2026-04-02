<div class="px-5 pb-8" x-data="productGrid()" x-init="boot()">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">Manajemen Inventory</h1>
            <p class="mt-1 text-sm text-slate-500">Double click row untuk edit cepat. Detail produk dan supplier tetap
                lengkap, tapi tampilannya saya jaga tetap ringkas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
                <span x-text="dirtyCount"></span> perubahan belum disimpan
            </span>
            <button type="button" @click="addRowTop()"
                class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
                <i class="fas fa-plus text-[10px]"></i>
                Tambah Baris
            </button>
            <button type="button" @click="submitForm()"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800">
                <i class="fas fa-save text-[11px]"></i>
                Simpan Perubahan
            </button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Products</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">{{ number_format($summary['total_produk']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Stock</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">{{ number_format($summary['total_stok']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Inventory Value</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">Rp {{ number_format($summary['nilai_inv'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-400">Low Stock Items</p>
            <h3 class="text-2xl font-semibold mt-2 {{ $summary['stok_menipis'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                {{ number_format($summary['stok_menipis']) }}
            </h3>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
            <p class="text-sm font-semibold text-red-700">Masih ada data yang perlu diperbaiki sebelum disimpan.</p>
            <ul class="mt-2 space-y-1 text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <form action="{{ route('products.index') }}" method="GET" class="flex flex-wrap gap-3 md:flex-nowrap">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari SKU atau nama produk..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-4 text-sm outline-none transition focus:border-slate-400">
            </div>
            <select name="category_id" onchange="this.form.submit()"
                class="min-w-52 cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-slate-400">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="submit"
                class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">Terapkan
                Filter</button>
        </form>
    </div>

    <form id="product-grid-form" action="{{ route('products.grid-save') }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

        <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50/80 text-slate-500">
                        <tr>
                            <th class="w-14 px-3 py-4 text-center text-[11px] font-medium uppercase tracking-wider">+
                            </th>
                            <th class="px-4 py-4 font-medium">Produk</th>
                            <th class="px-4 py-4 font-medium">Kategori</th>
                            <th class="px-4 py-4 font-medium">Ringkasan Supplier</th>
                            <th class="px-4 py-4 font-medium">Harga & Stok</th>
                            <th class="px-4 py-4 font-medium">Spesifikasi</th>
                            <th class="px-4 py-4 text-center font-medium">Status</th>
                            <th class="px-4 py-4 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>

                    <template x-for="row in rows" :key="row.client_key">
                        <tbody class="group divide-y divide-slate-100" :data-row-key="row.client_key">
                            <tr @dblclick="activateRow(row)"
                                :class="row.marked_for_delete ? 'bg-rose-50/70 opacity-70' : (row.is_editing ?
                                    'bg-slate-50/70' : 'hover:bg-slate-50/70')"
                                class="transition">
                                <td class="px-3 py-3 text-center">
                                    <button type="button" @click="addRowAfter(row.client_key)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 opacity-0 transition hover:border-slate-300 hover:text-slate-900 group-hover:opacity-100">
                                        <i class="fas fa-plus text-[11px]"></i>
                                    </button>
                                </td>

                                <td class="px-4 py-3">
                                    <template x-if="row.is_editing && !row.marked_for_delete">
                                        <div class="space-y-2">
                                            <input x-model="row.name" @input="markDirty(row)" data-focus="name"
                                                type="text"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400"
                                                placeholder="Nama produk">
                                            <div class="flex flex-wrap gap-2 text-[10px] text-slate-400">
                                                <span class="font-mono"
                                                    x-text="row.id ? `#PRD-${String(row.id).padStart(4, '0')}` : '#DRAFT'"></span>
                                                <span x-show="row.is_dirty"
                                                    class="rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-600">Unsaved</span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!row.is_editing || row.marked_for_delete">
                                        <div class="space-y-1">
                                            <div class="font-semibold leading-tight text-slate-800"
                                                x-text="row.name || 'Produk baru'"></div>
                                            <div class="text-[10px] text-slate-400"
                                                x-text="row.id ? `#PRD-${String(row.id).padStart(4, '0')}` : '#DRAFT'">
                                            </div>
                                        </div>
                                    </template>
                                </td>

                                <td class="px-4 py-3">
                                    <template x-if="row.is_editing && !row.marked_for_delete">
                                        <select x-model="row.category_id" @change="changeCategory(row)"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400">
                                            <option value="">Pilih kategori</option>
                                            <template x-for="category in categories" :key="category.id">
                                                <option :value="String(category.id)" x-text="category.name"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="!row.is_editing || row.marked_for_delete">
                                        <span
                                            class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-600"
                                            x-text="categoryName(row)"></span>
                                    </template>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-2">
                                        <span class="text-[11px] text-slate-600" x-text="supplierSummary(row)"></span>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="supplierPreviewRow in supplierPreview(row)"
                                                :key="`${row.client_key}-supplier-preview-${supplierPreviewRow.index}`">
                                                <span
                                                    class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[10px] font-medium ring-1"
                                                    :class="supplierTone(supplierPreviewRow.index).badge">
                                                    <span class="h-2 w-2 rounded-full"
                                                        :class="supplierTone(supplierPreviewRow.index).dot"></span>
                                                    <span class="truncate max-w-[110px]"
                                                        x-text="supplierPreviewRow.name"></span>
                                                    <span class="opacity-70" x-text="supplierPreviewRow.condition"></span>
                                                </span>
                                            </template>
                                            <span x-show="remainingSupplierCount(row) > 0"
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-medium text-slate-500">
                                                +<span x-text="remainingSupplierCount(row)"></span> lagi
                                            </span>
                                        </div>
                                        <span x-show="hasDuplicateSupplierCondition(row)"
                                            class="text-[10px] font-medium text-rose-500">Kombinasi supplier + kondisi
                                            duplikat, satukan di satu baris supplier.</span>
                                        <button type="button" @click="openSupplierModal(row)"
                                            class="w-fit rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-medium text-white transition hover:bg-slate-800">Kelola
                                            Supplier</button>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="space-y-2" x-show="activeSupplierEntries(row).length > 0">
                                        <template x-for="supplierEntry in activeSupplierEntries(row)" :key="`${row.client_key}-metric-${supplierEntry.index}`">
                                            <div class="flex flex-wrap items-center gap-2 text-[10px]">
                                                <span class="h-2.5 w-2.5 rounded-full flex-none" :class="supplierTone(supplierEntry.index).dot"></span>
                                                <template x-for="metricBadge in supplierMetricBadges(row, supplierEntry, supplierEntry.index)" :key="`${row.client_key}-${supplierEntry.index}-${metricBadge.key}`">
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 font-medium" :class="metricBadge.className" x-text="metricBadge.label"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="activeSupplierEntries(row).length === 0" class="text-[11px] text-slate-400">
                                        Belum ada data harga supplier.
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-2">
                                        <span class="text-[11px] text-slate-500" x-text="specSummary(row)"></span>
                                        <button type="button" @click="openDetailModal(row)"
                                            class="w-fit rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-medium text-white transition hover:bg-slate-800">Kelola
                                            Spec</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center"><span
                                        class="rounded-full px-2.5 py-1 text-[10px] font-medium"
                                        :class="statusMeta(row).className" x-text="statusMeta(row).label"></span></td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-end">
                                        <button type="button" @click="toggleDelete(row)"
                                            class="rounded-lg p-2 transition"
                                            :class="row.marked_for_delete ? 'bg-emerald-50 text-emerald-600' :
                                                'text-red-400 hover:bg-red-50 hover:text-red-600'"><i
                                                class="fas"
                                                :class="row.marked_for_delete ? 'fa-rotate-left' : 'fa-trash'"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr x-show="row.marked_for_delete" x-cloak>
                                <td colspan="8" class="bg-rose-50/80 px-6 py-4 text-sm text-rose-700">Baris ini
                                    akan dihapus saat tombol simpan ditekan.</td>
                            </tr>
                            <tr class="hidden">
                                <td colspan="8"><template x-for="field in hiddenFields(row)"
                                        :key="field.name"><input type="hidden" :name="field.name"
                                            :value="field.value"></template></td>
                            </tr>
                        </tbody>
                    </template>
                </table>
            </div>
        </div>
    </form>

    <x-modal id="modal-product-detail" title="Detail Produk" size="xl">
        <template x-if="activeDetailRow()">
            <div class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Garansi</label>
                        <input x-model="activeDetailRow().warranty" @input="markDirty(activeDetailRow())"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Foto
                            Produk</label>
                        <input :name="activeDetailRow() ? `products[${activeDetailRow().client_key}][image]` : ''"
                            @change="onImageChange($event, activeDetailRow())" type="file" accept="image/*"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
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
                                        <button type="button" @click="setSpecMode(activeDetailRow(), field.key, 'existing')"
                                            :class="activeDetailRow()?.specs[field.key]?.mode === 'existing' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Pilih Referensi</button>
                                        <button type="button" @click="setSpecMode(activeDetailRow(), field.key, 'new')"
                                            :class="activeDetailRow()?.specs[field.key]?.mode === 'new' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Input Baru</button>
                                    </div>

                                    <div x-show="activeDetailRow()?.specs[field.key]?.mode !== 'new'" x-cloak>
                                        <label class="mb-1 block text-[11px] font-medium text-slate-400">Gunakan value yang sudah ada</label>
                                        <select :value="activeDetailRow()?.specs[field.key]?.value || ''"
                                            @change="updateSpec(activeDetailRow(), field.key, $event.target.value)"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-slate-400">
                                            <option value="">Pilih value</option>
                                            <template x-for="option in activeDetailRow() ? specOptions(activeDetailRow(), field.key) : []"
                                                :key="`detail-${field.key}-${option}`">
                                                <option :value="option" x-text="option"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div x-show="activeDetailRow()?.specs[field.key]?.mode === 'new'" x-cloak>
                                        <label class="mb-1 block text-[11px] font-medium text-slate-400">Input value baru</label>
                                        <input :value="activeDetailRow()?.specs[field.key]?.value || ''"
                                            @input="updateSpec(activeDetailRow(), field.key, $event.target.value)"
                                            @blur="normalizeSpecEntry(activeDetailRow(), field.key)"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-slate-400"
                                            :placeholder="field.placeholder || 'Masukkan value baru'">
                                        <p class="mt-1 text-[11px] text-slate-400">Jika penulisannya ternyata sama dengan referensi lama, sistem akan otomatis menyamakan formatnya.</p>
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
            </div>
        </template>
    </x-modal>

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
                                    <select x-model="supplierRow.supplier_id"
                                        @change="onSupplierSelectChange(activeSupplierRow(), supplierRow)"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400 w-full">
                                        <option value="">Pilih supplier</option>
                                        <template x-for="supplier in suppliers"
                                            :key="`modal-${supplierIndex}-${supplier.id}`">
                                            <option :value="String(supplier.id)" x-text="supplier.name"></option>
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
                                            beli</label>
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
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </x-modal>

    <div class="mt-6">{{ $products->links() }}</div>
</div>
