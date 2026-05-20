{{-- SEARCH + FILTER BAR --}}
<div class="mb-6">
    <div
        class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-3 shadow-sm md:flex-row md:items-center">
        {{-- SEARCH INPUT --}}
        <div class="relative min-w-0 flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            <input type="text" x-model="searchQuery" placeholder="Cari part, serial number, atau 4 digit belakang..."
                class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:bg-white">
        </div>

        {{-- CATEGORY DROPDOWN --}}
        <div x-show="transactionData.transactionMode === 'sparepart'" x-cloak class="relative md:w-56"
            x-data="{ open: false }">
            <button @click="open = !open"
                class="flex h-11 w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 transition hover:border-slate-300 hover:bg-white">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="text-sm text-slate-400"><i class="fas fa-layer-group"></i></span>
                    <span class="truncate" x-text="activeCat"></span>
                </div>
                <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform"
                    :class="open ? 'rotate-180' : ''"></i>
            </button>
            <div x-show="open" @click.away="open = false" x-transition
                class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl">
                <button @click="activeCat = 'Semua'; open = false"
                    class="flex w-full items-center justify-between px-4 py-3 text-left text-sm hover:bg-slate-50"
                    :class="activeCat === 'Semua' ? 'bg-slate-50 text-slate-900' : 'text-slate-600'">Semua</button>
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="activeCat = cat.name; open = false"
                        class="flex w-full items-center justify-between px-4 py-3 text-left text-sm hover:bg-slate-50"
                        :class="activeCat === cat.name ? 'bg-slate-50 text-slate-900' : 'text-slate-600'">
                        <span x-text="cat.name"></span>
                        <i x-show="activeCat === cat.name" class="fas fa-check text-[10px]"></i>
                    </button>
                </template>
            </div>
        </div>

        {{-- COMPATIBILITY TOGGLE
        <button @click="toggleCompatibility()"
            :class="filterCompatible ? 'bg-slate-900 text-white border-slate-900' :
                    'bg-slate-50 text-slate-600 border-slate-200'"
            class="flex h-11 items-center gap-2 rounded-xl border px-4 text-sm transition hover:border-slate-300">
            <i class="fas fa-microchip"></i>
            <span x-text="filterCompatible ? 'Compatibility aktif' : 'Match compatibility'"></span>
        </button> --}}
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button @click="setTransactionMode('sparepart')"
            :class="transactionData.transactionMode === 'sparepart' ? 'bg-slate-900 text-white border-slate-900' :
                'bg-white text-slate-600 border-slate-200'"
            class="h-11 rounded-xl border px-4 text-sm transition">
            Sparepart only
        </button>
        <button @click="setTransactionMode('rakit_pc')"
            :class="transactionData.transactionMode === 'rakit_pc' ? 'bg-slate-900 text-white border-slate-900' :
                'bg-white text-slate-600 border-slate-200'"
            class="h-11 rounded-xl border px-4 text-sm transition">
            Rakit PC
        </button>

    </div>
</div>
