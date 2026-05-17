<div class="px-4 pb-6 lg:px-5 lg:pb-8" x-data="productInventory()" x-init="boot()">
    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Manajemen Inventory</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manajemen produk di halaman ini.
            </p>
        </div>

        <button type="button" @click="openCreateProductModal()"
            class="inline-flex w-max items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-[13px] font-medium text-white shadow-sm transition hover:bg-slate-800">
            <i class="fas fa-plus text-[10px]"></i>
            Tambah Produk
        </button>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Total Products</p>
            <h3 class="mt-2 text-[18px] font-semibold text-slate-900">{{ number_format($summary['total_produk']) }}</h3>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Total Stock</p>
            <h3 class="mt-2 text-[18px] font-semibold text-slate-900">{{ number_format($summary['total_stok']) }}</h3>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Inventory Value</p>
            <h3 class="mt-2 text-[18px] font-semibold text-slate-900">Rp
                {{ number_format($summary['nilai_inv'], 0, ',', '.') }}</h3>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Low Stock Items</p>
            <h3
                class="mt-2 text-[18px] font-semibold {{ $summary['stok_menipis'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                {{ number_format($summary['stok_menipis']) }}
            </h3>
        </div>
    </div>

    <div class="mb-5 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-sm">
        <form action="{{ route('products.index') }}" method="GET" class="flex flex-wrap gap-3 md:flex-nowrap">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-[13px] text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama produk atau serial number..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-[13px] outline-none transition focus:border-slate-400">
            </div>
            <select name="category_id" onchange="this.form.submit()"
                class="min-w-48 cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[13px] outline-none transition focus:border-slate-400">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="submit"
                class="rounded-xl bg-slate-900 px-5 py-2.5 text-[13px] font-medium text-white transition hover:bg-slate-800">Terapkan
                Filter</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1120px] w-full text-left text-[13px] xl:table-fixed">
                <colgroup>
                    <col class="w-[6%]">
                    <col class="w-[16%]">
                    <col class="w-[11%]">
                    <col class="w-[13%]">
                    <col class="w-[16%]">
                    <col class="w-[11%]">
                    <col class="w-[9%]">
                    <col class="w-[11%]">
                    <col class="w-[8%]">
                    <col class="w-[11%]">
                    <col class="w-[10%]">
                </colgroup>
                <thead class="bg-slate-50/80 text-slate-500">
                    <tr>
                        <th class="px-3 py-3 text-center text-[12px] font-medium">Gambar</th>
                        <th class="px-3 py-3 text-[12px] font-medium">Produk</th>
                        <th class="px-3 py-3 text-[12px] font-medium">Brand</th>
                        <th class="px-3 py-3 text-[12px] font-medium">Kategori</th>
                        <th class="px-3 py-3 text-[12px] font-medium">Supplier</th>
                        <th class="px-3 py-3 text-[12px] font-medium">Spesifikasi</th>
                        <th class="px-3 py-3 text-right text-[12px] font-medium">Total Stok</th>
                        <th class="px-3 py-3 text-[12px] font-medium">Pemodal</th>
                        <th class="px-3 py-3 text-center text-[12px] font-medium whitespace-nowrap">Status</th>
                        <th class="px-3 py-3 text-[12px] font-medium whitespace-nowrap">Letak Barang</th>
                        <th class="px-3 py-3 text-right pr-8 text-[12px] font-medium whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="row in rows" :key="row.client_key">
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="px-3 py-3 align-top">
                                <button type="button" @click="openImageModal(row)"
                                    class="mx-auto block h-12 w-12 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 transition hover:border-slate-300 hover:shadow-sm"
                                    :aria-label="`Lihat gambar ${row.name || 'produk'}`">
                                    <img :src="productImageUrl(row)" :alt="row.name || 'Foto produk'"
                                        class="h-full w-full object-cover" x-on:error="$event.target.src = noImageUrl">
                                </button>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <div class="space-y-1">
                                    <div class="text-[13px] font-semibold leading-[1.35] text-slate-800"
                                        x-text="row.name || 'Produk'"></div>
                                    <span x-show="row.serial_number" x-cloak
                                        class="font-mono text-[10px] text-slate-500" x-text="row.serial_number"></span>
                                </div>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <span class="text-[12px] font-medium text-slate-700" x-text="row.brand || '-'"></span>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-600"
                                    x-text="categoryName(row)"></span>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="supplierPreviewRow in supplierPreview(row, 2)"
                                        :key="`${row.client_key}-supplier-preview-${supplierPreviewRow.index}`">
                                        <span
                                            class="inline-flex max-w-full items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium ring-1"
                                            :class="supplierPreviewBadgeClass(supplierPreviewRow.condition)">
                                            <span class="truncate max-w-27.5" x-text="supplierPreviewRow.name"></span>
                                            <span class="opacity-70" x-text="supplierPreviewRow.condition"></span>
                                        </span>
                                    </template>
                                    <span x-show="remainingSupplierCount(row, 2) > 0"
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">
                                        +<span x-text="remainingSupplierCount(row, 2)"></span> lagi
                                    </span>
                                    <span x-show="activeSupplierEntries(row).length === 0"
                                        class="text-[11px] text-slate-400">Belum ada supplier</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <span class="text-[11px] leading-4 text-slate-500" x-text="specSummary(row)"></span>
                            </td>
                            <td class="px-3 py-3 align-top text-right">
                                <span class="text-[15px] font-semibold leading-5 text-slate-900 tabular-nums"
                                    x-text="formatNumber(rowStock(row))"></span>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <span class="text-[12px] font-medium text-slate-700"
                                    x-text="investorSummary(row)"></span>
                            </td>
                            <td class="px-3 py-3 text-center align-top">
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-medium"
                                    :class="statusMeta(row).className" x-text="statusMeta(row).label"></span>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <span class="text-[12px] text-slate-700" x-text="row.letak_barang || '-'"></span>
                            </td>
                            <td class="px-3 py-3 align-top">
                                <div class="flex justify-end gap-1">
                                    <button type="button" @click="openPreviewModal(row)"
                                        class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                                        <i class="fas fa-eye text-[13px]"></i>
                                    </button>
                                    <button type="button" @click="openEditProductModal(row)"
                                        class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                                        <i class="fas fa-pen text-[13px]"></i>
                                    </button>
                                    <form :action="deleteAction(row)" method="POST"
                                        onsubmit="return confirm('Hapus produk ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg p-1.5 text-red-400 transition hover:bg-red-50 hover:text-red-600">
                                            <i class="fas fa-trash text-[13px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="rows.length === 0" x-cloak>
                        <td colspan="11" class="px-5 py-10 text-center text-sm text-slate-400">
                            Belum ada produk pada filter ini. Klik Tambah Produk untuk membuat data baru.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <x-modal id="modal-product-form" title="Form Produk" size="xl">
        <template x-if="formReady && formRow">
            <form id="product-form" :action="productFormAction()" method="POST" enctype="multipart/form-data"
                @submit.prevent="submitProductForm($event)" class="space-y-6">
                @csrf
                <input type="hidden" name="_method" :value="formMode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="_form_mode" :value="formMode">
                <input type="hidden" name="_product_id" :value="formRow?.id || ''">

                <div class="space-y-6">
                    <div x-show="formErrors.length > 0" x-cloak
                        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-sm font-semibold text-red-700">Masih ada data yang perlu diperbaiki.</p>
                        <ul class="mt-2 space-y-1 text-sm text-red-600">
                            <template x-for="(message, errorIndex) in formErrors" :key="`form-error-${errorIndex}`">
                                <li x-text="message"></li>
                            </template>
                        </ul>
                    </div>

                    <div class="border-b border-slate-100 pb-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900"
                                x-text="formMode === 'create' ? 'Tambah Produk' : 'Edit Produk'"></h3>
                            <p class="mt-1 text-sm text-slate-500"
                                x-text="formRow.name || 'Lengkapi data produk, supplier, harga, dan spesifikasi.'"></p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Nama
                                Produk</label>
                            <input name="name" x-model="formRow.name" type="text"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400"
                                placeholder="Nama produk">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Nomor
                                Seri</label>
                            <input name="serial_number" x-model="formRow.serial_number" type="text"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400"
                                placeholder="Serial number">
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Brand</label>
                            <input name="brand" x-model="formRow.brand" type="text"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400"
                                placeholder="Brand">
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Kategori</label>
                            <select name="category_id" x-model="formRow.category_id"
                                @change="changeCategory(formRow)"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                <option value="">Pilih kategori</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="String(category.id)" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Foto
                                Produk</label>
                            <input name="image" @change="onImageChange($event)" type="file" accept="image/*"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                            <p class="mt-1 text-xs text-slate-500"
                                x-text="formRow._imageName ? `File baru: ${formRow._imageName}` : (formRow.image_url ? 'Gambar lama tersimpan.' : 'Belum ada gambar.')">
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Letak
                                Barang</label>
                            <input name="letak_barang" x-model="formRow.letak_barang" type="text"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400"
                                placeholder="Rak / gudang / etalase">
                        </div>
                        <div class="md:col-span-2">
                            <label
                                class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Deskripsi</label>
                            <textarea name="description" x-model="formRow.description" rows="3"
                                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400"
                                placeholder="Deskripsi produk"></textarea>
                        </div>
                    </div>

                    <div class="space-y-4 border-t border-slate-100 pt-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">Supplier, Stok, dan Harga</h3>
                                <p class="mt-1 text-xs text-slate-500">Pilih supplier lama atau buat supplier baru.</p>
                            </div>
                            <button type="button" @click="addSupplier(formRow)"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                                <i class="fas fa-plus text-[10px]"></i>
                                Tambah Supplier
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(supplierRow, supplierIndex) in formRow.suppliers"
                                :key="`form-supplier-${supplierIndex}`">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
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
                                        <button type="button" @click="removeSupplier(formRow, supplierIndex)"
                                            class="text-sm text-red-500 transition hover:text-red-600">Hapus</button>
                                    </div>

                                    <input type="hidden" :name="`suppliers[${supplierIndex}][mode]`"
                                        :value="supplierRow.mode || 'existing'">

                                    <div class="grid gap-3">
                                        <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <label
                                                    class="block text-xs font-medium uppercase tracking-wider text-slate-400">Sumber
                                                    Supplier</label>
                                                <div class="flex gap-2">
                                                    <button type="button"
                                                        @click="setSupplierMode(formRow, supplierRow, 'existing')"
                                                        :class="supplierRow.mode === 'existing' ? 'bg-slate-900 text-white' :
                                                            'bg-slate-100 text-slate-600'"
                                                        class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Supplier
                                                        Lama</button>
                                                    <button type="button"
                                                        @click="setSupplierMode(formRow, supplierRow, 'new')"
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
                                            <select :name="`suppliers[${supplierIndex}][supplier_id]`"
                                                :disabled="supplierRow.mode !== 'existing'"
                                                x-model="supplierRow.supplier_id"
                                                @change="onSupplierSelectChange(formRow, supplierRow)"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                                <option value="">Pilih supplier</option>
                                                <template x-for="supplier in suppliers"
                                                    :key="`form-${supplierIndex}-${supplier.id}`">
                                                    <option :value="String(supplier.id)" x-text="supplier.name">
                                                    </option>
                                                </template>
                                                <option value="__new__">+ Input supplier baru</option>
                                            </select>
                                        </div>

                                        <div x-show="supplierRow.mode === 'new'" x-cloak
                                            class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Nama
                                                    Supplier Baru</label>
                                                <input :name="`suppliers[${supplierIndex}][new_supplier_name]`"
                                                    :disabled="supplierRow.mode !== 'new'"
                                                    x-model="supplierRow.new_supplier_name" type="text"
                                                    placeholder="Contoh: CV Sumber Jaya"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Alamat
                                                    Supplier Baru</label>
                                                <input :name="`suppliers[${supplierIndex}][new_supplier_address]`"
                                                    :disabled="supplierRow.mode !== 'new'"
                                                    x-model="supplierRow.new_supplier_address" type="text"
                                                    placeholder="Alamat supplier"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                            </div>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Pemodal</label>
                                                <select :name="`suppliers[${supplierIndex}][pemodal_user_id]`"
                                                    x-model="supplierRow.pemodal_user_id"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                                    <option value="">Pilih pemodal</option>
                                                    <template x-for="user in users"
                                                        :key="`form-user-${supplierIndex}-${user.id}`">
                                                        <option :value="String(user.id)" x-text="user.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Kondisi</label>
                                                <select :name="`suppliers[${supplierIndex}][condition]`"
                                                    x-model="supplierRow.condition"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                                    <template x-for="condition in conditionOptions"
                                                        :key="`form-cond-${supplierIndex}-${condition}`">
                                                        <option :value="condition" x-text="condition"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Stok</label>
                                                <input :name="`suppliers[${supplierIndex}][stock]`"
                                                    x-model="supplierRow.stock" type="number" min="0"
                                                    placeholder="Stok"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Harga
                                                    Modal</label>
                                                <input :name="`suppliers[${supplierIndex}][harga_beli]`"
                                                    x-model="supplierRow.harga_beli" type="number" min="0"
                                                    placeholder="Harga beli"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Harga
                                                    Jual</label>
                                                <input :name="`suppliers[${supplierIndex}][harga_jual]`"
                                                    x-model="supplierRow.harga_jual" type="number" min="0"
                                                    placeholder="Harga jual"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Garansi
                                                    Supplier</label>
                                                <input :name="`suppliers[${supplierIndex}][warranty_detail]`"
                                                    x-model="supplierRow.warranty_detail" type="text"
                                                    placeholder="Garansi dari supplier"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-4 border-t border-slate-100 pt-5">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800">Spesifikasi Utama</h3>
                            <p class="mt-1 text-xs text-slate-500">Template mengikuti kategori produk.</p>
                        </div>
                        <div x-show="templateFields(formRow).length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            Pilih kategori untuk memunculkan field spesifikasi.</div>
                        <div x-show="templateFields(formRow).length > 0" class="grid gap-4 md:grid-cols-2">
                            <template x-for="field in templateFields(formRow)" :key="`form-detail-${field.key}`">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <input type="hidden" :name="`specs[${field.key}][key]`" :value="field.key">
                                    <input type="hidden" :name="`specs[${field.key}][mode]`"
                                        :value="formRow.specs[field.key]?.mode || 'existing'">
                                    <div class="flex items-center justify-between gap-2">
                                        <label class="text-sm font-medium text-slate-700"
                                            x-text="field.label"></label>
                                        <span class="rounded-full px-2 py-1 text-[10px]"
                                            :class="field.required ? 'bg-slate-900 text-white' :
                                                'bg-white text-slate-500'"
                                            x-text="field.required ? 'Required' : 'Optional'"></span>
                                    </div>
                                    <div class="mt-3 space-y-3">
                                        <div class="flex gap-2">
                                            <button type="button"
                                                @click="setSpecMode(formRow, field.key, 'existing')"
                                                :class="formRow.specs[field.key]?.mode === 'existing' ?
                                                    'bg-slate-900 text-white' :
                                                    'bg-white text-slate-600 ring-1 ring-slate-200'"
                                                class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Pilih
                                                Referensi</button>
                                            <button type="button" @click="setSpecMode(formRow, field.key, 'new')"
                                                :class="formRow.specs[field.key]?.mode === 'new' ?
                                                    'bg-slate-900 text-white' :
                                                    'bg-white text-slate-600 ring-1 ring-slate-200'"
                                                class="rounded-lg px-3 py-1.5 text-[11px] font-medium transition">Input
                                                Baru</button>
                                        </div>

                                        <div x-show="formRow.specs[field.key]?.mode !== 'new'" x-cloak>
                                            <label class="mb-1 block text-[11px] font-medium text-slate-400">Gunakan
                                                value yang sudah ada</label>
                                            <select :name="`specs[${field.key}][value]`"
                                                :disabled="formRow.specs[field.key]?.mode === 'new'"
                                                x-model="formRow.specs[field.key].value"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-slate-400">
                                                <option value="">Pilih value</option>
                                                <template x-for="option in specSelectOptions(formRow, field.key)"
                                                    :key="`form-detail-${field.key}-${option}`">
                                                    <option :value="option" x-text="option"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div x-show="formRow.specs[field.key]?.mode === 'new'" x-cloak>
                                            <label class="mb-1 block text-[11px] font-medium text-slate-400">Input
                                                value
                                                baru</label>
                                            <input :name="`specs[${field.key}][value]`"
                                                :disabled="formRow.specs[field.key]?.mode !== 'new'"
                                                :value="formRow.specs[field.key]?.value || ''"
                                                @input="updateSpec(formRow, field.key, $event.target.value)"
                                                @blur="normalizeSpecEntry(formRow, field.key)"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-slate-400"
                                                :placeholder="field.placeholder || 'Masukkan value baru'">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-4 border-t border-slate-100 pt-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">Spesifikasi Tambahan</h3>
                                <p class="mt-1 text-xs text-slate-500">Atribut non-template tetap bisa disimpan.</p>
                            </div>
                            <button type="button" @click="addExtraSpec(formRow)"
                                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">Tambah</button>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(extraSpec, extraIndex) in formRow.additional_specs"
                                :key="`form-extra-${extraIndex}`">
                                <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-medium text-slate-500">Spec #<span
                                                x-text="extraIndex + 1"></span></span>
                                        <button type="button" @click="removeExtraSpec(formRow, extraIndex)"
                                            class="rounded-lg bg-white px-3 py-1.5 text-xs text-red-500 ring-1 ring-slate-200 transition hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label
                                                class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-slate-400">Nama
                                                Field</label>
                                            <select x-model="extraSpec._selectedKey"
                                                @change="onExtraSpecKeySelect(formRow, extraSpec, extraSpec._selectedKey)"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                                <option value="">-- Pilih nama field --</option>
                                                <template x-for="knownKey in allKnownSpecKeys" :key="knownKey.key">
                                                    <option :value="knownKey.key"
                                                        x-text="`${knownKey.label} - ${knownKey.key}`"></option>
                                                </template>
                                                <option value="__custom__">Lainnya (tulis sendiri)</option>
                                            </select>
                                            <input x-show="extraSpec._selectedKey === '__custom__'" x-cloak
                                                :disabled="extraSpec._selectedKey !== '__custom__'"
                                                :name="`extra_specs[${extraIndex}][key]`" x-model="extraSpec.key"
                                                type="text" placeholder="Contoh: refresh_rate"
                                                class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                            <input x-show="extraSpec._selectedKey !== '__custom__'" x-cloak
                                                :disabled="extraSpec._selectedKey === '__custom__'" type="hidden"
                                                :name="`extra_specs[${extraIndex}][key]`"
                                                :value="extraSpec.key || ''">
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-slate-400">Nilai</label>
                                            <input :name="`extra_specs[${extraIndex}][value]`"
                                                x-model="extraSpec.value" type="text" placeholder="Masukkan nilai"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-slate-400">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <button type="button" @click="closeProductFormModal()"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSaving"
                            :class="isSaving ? 'cursor-not-allowed bg-slate-300 text-slate-500' :
                                'bg-slate-900 text-white hover:bg-slate-800'"
                            class="rounded-xl px-4 py-2 text-sm font-medium transition">
                            <span x-text="isSaving ? 'Menyimpan...' : 'Simpan'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </template>
    </x-modal>

    <x-modal id="modal-product-image" title="Foto Produk" size="lg">
        <template x-if="activePreviewRow()">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                    <img :src="activePreviewImage()" :alt="activePreviewRow()?.name || 'Foto produk'"
                        class="max-h-[72vh] w-full object-contain" x-on:error="$event.target.src = noImageUrl">
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900" x-text="activePreviewRow()?.name || 'Produk'"></p>
                    <p class="mt-1 font-mono text-xs text-slate-500"
                        x-text="activePreviewRow()?.serial_number || 'Tanpa nomor seri'"></p>
                </div>
            </div>
        </template>
    </x-modal>

    <x-modal id="modal-product-preview" title="Detail Produk" size="xl">
        <template x-if="activePreviewRow()">
            <div class="space-y-5">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                        <div class="aspect-square w-full bg-white">
                            <img :src="activePreviewImage()" :alt="activePreviewRow()?.name || 'Foto produk'"
                                class="h-full w-full object-cover" x-on:error="$event.target.src = noImageUrl">
                        </div>
                        <div class="border-t border-slate-200 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Foto Produk</p>
                            <p class="mt-1 truncate text-sm font-medium text-slate-800"
                                x-text="activePreviewRow()?.name || 'Produk'"></p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Produk</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900"
                            x-text="activePreviewRow()?.name || 'Produk'"></h3>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
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
                                <span class="text-slate-500">Nomor Seri</span>
                                <span class="font-mono font-semibold text-slate-900"
                                    x-text="activePreviewRow()?.serial_number || '-'"></span>
                            </div>
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
                                        <td class="px-4 py-3 text-right font-medium text-slate-900 tabular-nums"
                                            x-text="formatNumber(supplierEntry.stock)"></td>
                                        <td class="px-4 py-3 text-right text-slate-700 tabular-nums"
                                            x-text="`Rp ${formatCurrency(supplierEntry.harga_beli)}`"></td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-900 tabular-nums"
                                            x-text="`Rp ${formatCurrency(supplierEntry.harga_jual)}`"></td>
                                    </tr>
                                </template>
                                <tr x-show="activeSupplierEntries(activePreviewRow()).length === 0">
                                    <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada
                                        supplier aktif untuk produk ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h4 class="text-sm font-semibold text-slate-800">Spesifikasi Utama</h4>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="spec in filledMainSpecs(activePreviewRow())"
                                :key="`preview-main-spec-${spec.key}`">
                                <div class="flex items-start justify-between gap-4 px-4 py-3 text-sm">
                                    <span class="text-slate-500" x-text="spec.label"></span>
                                    <span class="max-w-72 text-right font-medium text-slate-900"
                                        x-text="spec.value"></span>
                                </div>
                            </template>
                            <div x-show="filledMainSpecs(activePreviewRow()).length === 0"
                                class="px-4 py-6 text-center text-sm text-slate-400">
                                Belum ada spesifikasi utama.
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h4 class="text-sm font-semibold text-slate-800">Spesifikasi Tambahan</h4>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="spec in filledExtraSpecs(activePreviewRow())"
                                :key="`preview-extra-spec-${spec.key}`">
                                <div class="flex items-start justify-between gap-4 px-4 py-3 text-sm">
                                    <span class="text-slate-500" x-text="spec.label"></span>
                                    <span class="max-w-72 text-right font-medium text-slate-900"
                                        x-text="spec.value"></span>
                                </div>
                            </template>
                            <div x-show="filledExtraSpecs(activePreviewRow()).length === 0"
                                class="px-4 py-6 text-center text-sm text-slate-400">
                                Belum ada spesifikasi tambahan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </x-modal>

    <div class="mt-6">{{ $products->links() }}</div>
</div>
