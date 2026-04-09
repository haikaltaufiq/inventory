<div class="px-4 pb-6 lg:px-5 lg:pb-8" x-data="productGrid()" x-init="boot()">
    @php
        $oldProducts = old('products', []);
        $productKeys = array_keys(is_array($oldProducts) ? $oldProducts : []);

        $gridErrorGroups = collect($errors->getMessages())->reduce(function (
            array $carry,
            array $messages,
            string $key,
        ) use ($oldProducts, $productKeys) {
            if (!\Illuminate\Support\Str::startsWith($key, 'products.')) {
                return $carry;
            }

            if (!preg_match('/^products\.([^.]+)\.(.+)$/', $key, $matches)) {
                return $carry;
            }

            $clientKey = $matches[1];
            $row = data_get($oldProducts, $clientKey, []);
            $rowIndex = array_search($clientKey, $productKeys, true);
            $rowNumber = $rowIndex === false ? null : $rowIndex + 1;
            $productId = (int) data_get($row, 'id', 0);
            $productName = trim((string) data_get($row, 'name', ''));
            $title =
                $productName !== ''
                    ? $productName
                    : ($productId > 0
                        ? '#PRD-' . str_pad((string) $productId, 4, '0', STR_PAD_LEFT)
                        : 'Produk baru');

            if (!isset($carry[$clientKey])) {
                $carry[$clientKey] = [
                    'client_key' => $clientKey,
                    'title' => $title,
                    'subtitle' => $rowNumber !== null ? 'Baris ' . $rowNumber : 'Produk draft',
                    'messages' => [],
                ];
            }

            foreach ($messages as $message) {
                if (!in_array($message, $carry[$clientKey]['messages'], true)) {
                    $carry[$clientKey]['messages'][] = $message;
                }
            }

            return $carry;
        }, []);

        $gridErrorGroups = array_values($gridErrorGroups);
        $gridErrorsByRow = collect($gridErrorGroups)
            ->mapWithKeys(fn(array $group) => [$group['client_key'] => $group['messages']])
            ->all();
        $generalErrors = collect($errors->getMessages())
            ->reject(fn(array $messages, string $key) => \Illuminate\Support\Str::startsWith($key, 'products.'))
            ->flatten()
            ->unique()
            ->values()
            ->all();
    @endphp

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-[29px] font-semibold tracking-tight text-slate-800">Manajemen Inventory</h1>
            <p class="mt-1 text-[13px] text-slate-500">Double click row untuk edit cepat. Detail produk dan supplier
                tetap
                lengkap</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-medium text-slate-600">
                <span x-text="dirtyCount"></span> perubahan belum disimpan
            </span>
            <button type="button" @click="addRowTop()"
                class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-[13px] font-medium text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
                <i class="fas fa-plus text-[10px]"></i>
                Tambah Baris
            </button>
            <button type="button" @click="submitForm()" :disabled="!hasChanges"
                :class="hasChanges ? 'bg-slate-900 hover:bg-slate-800 text-white shadow-sm' :
                    'bg-slate-200 text-slate-500 cursor-not-allowed'"
                class="inline-flex items-center gap-2 rounded-xl px-5 py-2 text-[13px] font-medium transition">
                <i class="fas fa-save text-[11px]"></i>
                Simpan Perubahan
            </button>
        </div>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Total Products</p>
            <h3 class="mt-2 text-[18px] font-semibold text-slate-900">{{ number_format($summary['total_produk']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Total Stock</p>
            <h3 class="mt-2 text-[18px] font-semibold text-slate-900">{{ number_format($summary['total_stok']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Inventory Value</p>
            <h3 class="mt-2 text-[18px] font-semibold text-slate-900">Rp
                {{ number_format($summary['nilai_inv'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Low Stock Items</p>
            <h3
                class="mt-2 text-[18px] font-semibold {{ $summary['stok_menipis'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                {{ number_format($summary['stok_menipis']) }}
            </h3>
        </div>
    </div>

    @if (!empty($gridErrorGroups) || !empty($generalErrors))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
            <p class="text-sm font-semibold text-red-700">Masih ada data yang perlu diperbaiki sebelum disimpan.</p>

            @if (!empty($gridErrorGroups))
                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    @foreach ($gridErrorGroups as $group)
                        <div class="rounded-2xl border border-red-200 bg-white/80 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-red-700">{{ $group['title'] }}</p>
                                    <p class="mt-1 text-xs text-red-500">{{ $group['subtitle'] }} •
                                        {{ count($group['messages']) }} hal perlu dicek</p>
                                </div>
                                <span
                                    class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-semibold text-red-700">
                                    {{ count($group['messages']) }} issue
                                </span>
                            </div>
                            <ul class="mt-3 space-y-1 text-sm text-red-600">
                                @foreach ($group['messages'] as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (!empty($generalErrors))
                <ul class="mt-4 space-y-1 text-sm text-red-600">
                    @foreach ($generalErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="mb-5 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-sm">
        <form action="{{ route('products.index') }}" method="GET" class="flex flex-wrap gap-3 md:flex-nowrap">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-[13px] text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari SKU atau nama produk..."
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
                        <label
                            class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Garansi</label>
                        <input x-model="activeDetailRow().warranty" @input="markDirty(activeDetailRow())"
                            type="text"
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
                                <span class="text-slate-500">Garansi</span>
                                <span class="font-medium text-slate-900 text-right"
                                    x-text="activePreviewRow()?.warranty || '-'"></span>
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

    <x-modal id="modal-unsaved-navigation" title="Perubahan Belum Disimpan" size="sm">
        <div class="space-y-5">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                    <i class="fas fa-exclamation-triangle text-base"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Simpan perubahan dulu?</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Ada perubahan data produk yang masih tersimpan sementara. Simpan dulu untuk tetap di halaman
                        ini, atau abaikan untuk lanjut pindah halaman.
                    </p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="closeUnsavedNavigationModal()"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Batal
                </button>
                <button type="button" @click="saveAndStay()"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                    Save
                </button>
                <button type="button" @click="ignoreUnsavedAndNavigate()"
                    class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-100">
                    Abaikan
                </button>
            </div>
        </div>
    </x-modal>

    <div class="mt-6">{{ $products->links() }}</div>
</div>
