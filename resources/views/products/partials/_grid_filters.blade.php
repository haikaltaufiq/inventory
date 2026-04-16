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
