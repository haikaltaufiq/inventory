<div class="bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition flex flex-col">

    {{-- IMAGE --}}
    <div class="bg-slate-50 rounded-xl aspect-square flex items-center justify-center overflow-hidden mb-4">
        @if ($image)
        <img
            src="{{ $image }}"
            alt="{{ $name }}"
            class="object-cover w-full h-full transition-transform duration-300 hover:scale-105">
        @else
        <div class="text-slate-300 text-sm">
            No Image
        </div>
        @endif
    </div>

    {{-- INFO --}}
    <div class="flex-1 flex flex-col">
        <h3 class="text-sm font-semibold text-slate-800 leading-snug line-clamp-2 mb-2">
            {{ $name }}
        </h3>

        <div class="flex items-center gap-2 mb-3">
            <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">
                {{ $category }}
            </span>
            <span class="text-xs text-slate-400">
                {{ $supplier }}
            </span>
        </div>

        <div class="mt-auto flex items-center justify-between">
            <span class="text-base font-semibold text-slate-900">
                Rp {{ number_format($price) }}
            </span>

            <button
                type="button"
                class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition"
                title="Tambah ke transaksi">
                <i class="fas fa-plus text-xs"></i>
            </button>
        </div>
    </div>
</div>