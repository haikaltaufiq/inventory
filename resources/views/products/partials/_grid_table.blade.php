<form id="product-grid-form" action="{{ route('products.grid-save') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-275 w-full text-left text-[13px] xl:table-fixed">
                <colgroup>
                    <col class="w-12">
                    <col class="w-[12%]">
                    <col class="w-[11%]">
                    <col class="w-[18%]">
                    <col class="w-[18%]">
                    <col class="w-[11%]">
                    <col class="w-[9%]">
                    <col class="w-[12%]">
                    <col class="w-[8%]">
                    <col class="w-[12%]">
                    <col class="w-[9%]">
                </colgroup>
                <thead class="bg-slate-50/80 text-slate-500">
                    <tr>
                        <th class="w-12 px-3 py-3 text-center text-[10px] font-medium uppercase tracking-[0.16em]">+
                        </th>
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

                <template x-for="row in rows" :key="row.client_key">
                    <tbody class="group divide-y divide-slate-100" :data-row-key="row.client_key">
                        <tr :class="row.marked_for_delete ? 'bg-rose-50/70 opacity-70' : (rowHasErrors(row) ?
                            'bg-rose-50/40 hover:bg-rose-50/60' :
                            (row.editing_cell ? 'bg-slate-50/60' : 'hover:bg-slate-50/60'))"
                            class="transition">
                            <td class="px-2.5 py-2.5 text-center align-top">
                                <button type="button" @click="addRowAfter(row.client_key)"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 opacity-0 transition hover:border-slate-300 hover:text-slate-900 group-hover:opacity-100">
                                    <i class="fas fa-plus text-[10px]"></i>
                                </button>
                            </td>
                            {{-- Product Name --}}
                            <td @dblclick.stop="activateRow(row, 'name')" class="px-3 py-1 align-top cursor-cell">
                                <div class="space-y-1">
                                    <template x-if="isCellEditing(row, 'name')">
                                        <input x-model="row.name" @input="markDirty(row)"
                                            @blur="saveCell(row, 'name')"
                                            @keydown.enter.prevent="saveCell(row, 'name', 'category')"
                                            @keydown.escape.prevent="stopCellEdit(row, 'name')"
                                            data-cell-input="name" type="text"
                                            class="w-full rounded-lg border border-slate-300 bg-white  py-1 text-[13px] outline-none transition focus:border-slate-400"
                                            placeholder="Nama produk">
                                    </template>
                                    <template x-if="!isCellEditing(row, 'name')">
                                        <div class="min-h-8.5">
                                            <div class="text-[13px] font-semibold leading-[1.35] text-slate-800"
                                                x-text="row.name || 'Produk baru'"></div>
                                        </div>
                                    </template>
                                    <div class="flex flex-wrap gap-1.5 text-[10px] text-slate-400">
                                        <span class="font-mono"
                                            x-text="row.id ? `#PRD-${String(row.id).padStart(4, '0')}` : '#DRAFT'"></span>
                                        <span x-show="row.is_dirty"
                                            class="rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-600">Unsaved</span>
                                        <span x-show="rowErrorCount(row) > 0"
                                            class="rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-600">
                                            <span x-text="`${rowErrorCount(row)} issue`"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Brand --}}
                            <td @dblclick.stop="activateRow(row, 'brand')"
                                class="px-3 py-2 align-top cursor-cell">
                                <template x-if="isCellEditing(row, 'brand')">
                                    <input x-model="row.brand" @input="markDirty(row)"
                                        @blur="saveCell(row, 'brand')"
                                        @keydown.enter.prevent="saveCell(row, 'brand')"
                                        @keydown.escape.prevent="stopCellEdit(row, 'brand')"
                                        data-cell-input="brand" type="text"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[13px] outline-none transition focus:border-slate-400"
                                        placeholder="Brand">
                                </template>
                                <template x-if="!isCellEditing(row, 'brand')">
                                    <div class="min-h-8.5 flex items-start">
                                        <span class="text-[12px] font-medium text-slate-700"
                                            x-text="row.brand || '-'"></span>
                                    </div>
                                </template>
                            </td>
                            {{-- Category --}}
                            <td @dblclick.stop="activateRow(row, 'category')"
                                class="px-3 py-2 align-top cursor-cell">
                                <template x-if="isCellEditing(row, 'category')">
                                    <select x-model="row.category_id"
                                        @change="changeCategory(row); saveCell(row, 'category')"
                                        @blur="saveCell(row, 'category')" data-cell-input="category"
                                        class="w-full max-w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[13px] outline-none transition focus:border-slate-400">
                                        <option value="">Pilih kategori</option>
                                        <template x-for="category in categories" :key="category.id">
                                            <option :value="String(category.id)" x-text="category.name"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="!isCellEditing(row, 'category')">
                                    <div class="min-h-8.5 flex items-start">
                                        <span
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-600"
                                            x-text="categoryName(row)"></span>
                                    </div>
                                </template>
                            </td>

                            {{-- Supplier --}}
                            <td class="px-3 py-1 align-top">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 space-y-1">

                                        <div class="flex flex-wrap gap-1">
                                            <template x-for="supplierPreviewRow in supplierPreview(row, 1)"
                                                :key="`${row.client_key}-supplier-preview-${supplierPreviewRow.index}`">
                                                <span
                                                    class="inline-flex max-w-full items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium ring-1"
                                                    :class="supplierPreviewBadgeClass(supplierPreviewRow.condition)">
                                                    <span class="h-1.5 w-1.5 rounded-full"
                                                        :class="supplierPreviewRow.condition === 'Used' ?
                                                            'bg-amber-500' : (supplierPreviewRow
                                                                .condition === 'Refurbished' ? 'bg-violet-500' :
                                                                'bg-sky-500')"></span>
                                                    <span class="truncate max-w-27.5"
                                                        x-text="supplierPreviewRow.name"></span>
                                                    <span class="opacity-70"
                                                        x-text="supplierPreviewRow.condition"></span>
                                                </span>
                                            </template>
                                            <span x-show="remainingSupplierCount(row, 1) > 0"
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">
                                                +<span x-text="remainingSupplierCount(row, 1)"></span> lagi
                                            </span>
                                        </div>
                                        <span x-show="hasDuplicateSupplierCondition(row)"
                                            class="text-[10px] font-medium text-rose-500">Kombinasi supplier +
                                            kondisi
                                            duplikat, satukan di satu baris supplier.</span>
                                    </div>
                                    <button type="button" @click="openSupplierModal(row)"
                                        class="flex h-7 w-7 flex-none items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900">
                                        <i class="fas fa-pen text-[11px]"></i>
                                    </button>
                                </div>
                            </td>

                            {{-- Specification --}}
                            <td class="px-3 py-2 align-top">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="min-w-0 text-[11px] leading-4 text-slate-500"
                                        x-text="specSummary(row)"></span>
                                    <button type="button" @click="openDetailModal(row)"
                                        class="flex h-7 w-7 flex-none items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900">
                                        <i class="fas fa-pen text-[11px]"></i>
                                    </button>
                                </div>
                            </td>
                            {{-- Stock --}}
                            <td class="px-3 py-2 align-top text-right">
                                <div class="min-h-8.5 flex flex-col items-end justify-start">
                                    <span class="text-[15px] font-semibold leading-5 text-slate-900 tabular-nums"
                                        x-text="formatNumber(rowStock(row))"></span>
                                    <span class="mt-0.5 text-[10px] text-slate-400"
                                        x-text="activeSupplierEntries(row).length > 0 ? `${activeSupplierEntries(row).length} supplier aktif` : 'Belum ada supplier'"></span>
                                </div>
                            </td>
                            {{-- Investor --}}
                            <td class="px-3 py-2 align-top">
                                <div class="min-h-8.5 flex items-start">
                                    <span class="text-[12px] font-medium text-slate-700"
                                        x-text="investorSummary(row)"></span>
                                </div>
                            </td>
                            {{-- Status --}}
                            <td class="px-3 py-2 text-center align-top"><span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-medium"
                                    :class="statusMeta(row).className" x-text="statusMeta(row).label"></span></td>
                            {{-- Location --}}
                            <td @dblclick.stop="activateRow(row, 'letak_barang')"
                                class="px-3 py-2 align-top cursor-cell">
                                <template x-if="isCellEditing(row, 'letak_barang')">
                                    <input x-model="row.letak_barang" @input="markDirty(row)"
                                        @blur="saveCell(row, 'letak_barang')"
                                        @keydown.enter.prevent="saveCell(row, 'letak_barang')"
                                        @keydown.escape.prevent="stopCellEdit(row, 'letak_barang')"
                                        data-cell-input="letak_barang" type="text"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[13px] outline-none transition focus:border-slate-400"
                                        placeholder="Rak / gudang / etalase">
                                </template>
                                <template x-if="!isCellEditing(row, 'letak_barang')">
                                    <div class="min-h-8.5 flex items-start">
                                        <span class="text-[12px] text-slate-700"
                                            x-text="row.letak_barang || '-'"></span>
                                    </div>
                                </template>
                            </td>

                            {{-- Action Button --}}
                            <td class="px-3 py-2 align-top">
                                <div class="flex justify-end gap-1">
                                    <button type="button" @click="openPreviewModal(row)"
                                        class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                                        <i class="fas fa-eye text-[13px]"></i>
                                    </button>
                                    <button type="button" @click="toggleDelete(row)"
                                        class="rounded-lg p-1.5 transition"
                                        :class="row.marked_for_delete ? 'bg-emerald-50 text-emerald-600' :
                                            'text-red-400 hover:bg-red-50 hover:text-red-600'"><i
                                            class="fas text-[13px]"
                                            :class="row.marked_for_delete ? 'fa-rotate-left' : 'fa-trash'"></i></button>
                                </div>
                            </td>
                        </tr>

                        <tr x-show="row.marked_for_delete" x-cloak>
                            <td colspan="10" class="bg-rose-50/80 px-5 py-3 text-[13px] text-rose-700">Baris ini
                                akan dihapus saat tombol simpan ditekan.</td>
                        </tr>
                        <tr x-show="rowErrorCount(row) > 0 && !row.marked_for_delete" x-cloak>
                            <td colspan="10" class="bg-rose-50/80 px-5 py-3">
                                <div class="flex flex-col gap-1.5">
                                    <p class="text-[13px] font-semibold text-rose-700">Field yang perlu dilengkapi
                                        untuk
                                        produk ini</p>
                                    <ul class="space-y-1 text-[13px] text-rose-600">
                                        <template x-for="(message, errorIndex) in rowErrors(row)"
                                            :key="`${row.client_key}-error-${errorIndex}`">
                                            <li x-text="message"></li>
                                        </template>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr class="hidden">
                            <td colspan="10">
                                <template x-for="field in hiddenFields(row)" :key="field.name">
                                    <input type="hidden" :name="field.name" :value="field.value">
                                </template>
                                <input :name="`products[${row.client_key}][image]`"
                                    :data-image-input="row.client_key" @change="onImageChange($event, row)"
                                    type="file" accept="image/*" class="hidden">
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>
    </div>
</form>
