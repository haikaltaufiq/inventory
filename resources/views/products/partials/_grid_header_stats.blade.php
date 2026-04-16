<div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <h1 class="text-[29px] font-semibold tracking-tight text-slate-800">Manajemen Inventory</h1>
        <p class="mt-1 text-[13px] text-slate-500">Double click row untuk edit cepat. Detail produk dan supplier tetap lengkap</p>
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
