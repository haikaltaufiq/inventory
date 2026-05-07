@extends('layouts.app')

@section('title', 'Simulasi Rakit PC')

@section('content')
<div class="px-5 pb-10">

    {{-- ===================================================================
         HEADER
    =================================================================== --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">Simulasi Rakit PC</h1>
            <p class="text-sm text-slate-500 mt-1">
                Pilih Motherboard terlebih dahulu — CPU dan RAM akan otomatis menyesuaikan.
            </p>
        </div>
        <button onclick="resetBuild()"
            class="px-4 py-2 rounded-xl bg-slate-100 text-sm font-medium text-slate-600 hover:bg-slate-200 transition">
            Reset Build
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ===============================================================
             KIRI: COMPONENT SELECTOR
             Dependency chain:
               Motherboard → selalu aktif (entry point)
               CPU         → aktif setelah Mobo; difilter by socket_type
               RAM         → aktif setelah Mobo; difilter by ram_type_slot → ram_type
               VGA         → aktif setelah CPU
               Storage     → aktif setelah Mobo (bebas pilih)
               PSU         → aktif setelah CPU + VGA; difilter by min_wattage
        =============================================================== --}}
        <div class="lg:col-span-2 space-y-3">

            {{-- 1. MOTHERBOARD --}}
            <div class="bg-white rounded-2xl shadow-sm p-5" id="row-motherboard">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div id="icon-motherboard"
                            class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                            1
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">Motherboard</p>
                            <p id="name-motherboard"
                               class="font-medium text-slate-400 italic text-sm truncate">Pilih Motherboard terlebih dahulu</p>
                            <p id="price-motherboard" class="text-xs text-emerald-600 font-medium mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('Motherboard', 'motherboard')"
                        id="btn-motherboard"
                        class="flex-shrink-0 px-4 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-700 transition font-medium">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 2. CPU --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50 transition-opacity duration-300" id="row-cpu">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div id="icon-cpu"
                            class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                            2
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">Processor (CPU)</p>
                            <p id="name-cpu"
                               class="font-medium text-slate-400 italic text-sm truncate">Pilih Motherboard dulu</p>
                            <p id="price-cpu" class="text-xs text-emerald-600 font-medium mt-0.5 hidden"></p>
                            <p id="hint-cpu" class="text-xs text-blue-500 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('Processor', 'cpu')"
                        id="btn-cpu" disabled
                        class="flex-shrink-0 px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition font-medium">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 3. RAM --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50 transition-opacity duration-300" id="row-ram">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div id="icon-ram"
                            class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                            3
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">RAM</p>
                            <p id="name-ram"
                               class="font-medium text-slate-400 italic text-sm truncate">Pilih Motherboard dulu</p>
                            <p id="price-ram" class="text-xs text-emerald-600 font-medium mt-0.5 hidden"></p>
                            <p id="hint-ram" class="text-xs text-blue-500 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('RAM', 'ram')"
                        id="btn-ram" disabled
                        class="flex-shrink-0 px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition font-medium">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 4. VGA --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50 transition-opacity duration-300" id="row-vga">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div id="icon-vga"
                            class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                            4
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">VGA / GPU</p>
                            <p id="name-vga"
                               class="font-medium text-slate-400 italic text-sm truncate">Pilih CPU dulu</p>
                            <p id="price-vga" class="text-xs text-emerald-600 font-medium mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('VGA', 'vga')"
                        id="btn-vga" disabled
                        class="flex-shrink-0 px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition font-medium">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 5. STORAGE --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50 transition-opacity duration-300" id="row-storage">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div id="icon-storage"
                            class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                            5
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">Storage</p>
                            <p id="name-storage"
                               class="font-medium text-slate-400 italic text-sm truncate">Pilih Motherboard dulu</p>
                            <p id="price-storage" class="text-xs text-emerald-600 font-medium mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('Storage', 'storage')"
                        id="btn-storage" disabled
                        class="flex-shrink-0 px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition font-medium">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 6. PSU --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50 transition-opacity duration-300" id="row-psu">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div id="icon-psu"
                            class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                            6
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">Power Supply</p>
                            <p id="name-psu"
                               class="font-medium text-slate-400 italic text-sm truncate">Pilih CPU & VGA dulu</p>
                            <p id="price-psu" class="text-xs text-emerald-600 font-medium mt-0.5 hidden"></p>
                            <p id="hint-psu" class="text-xs text-blue-500 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('Power Supply', 'psu')"
                        id="btn-psu" disabled
                        class="flex-shrink-0 px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition font-medium">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- STATUS KOMPATIBILITAS --}}
            <div id="compat-status" class="hidden rounded-2xl p-5 transition-all"></div>

        </div>

        {{-- ===============================================================
             KANAN: BUILD SUMMARY
        =============================================================== --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col h-fit sticky top-5">
            <h2 class="text-base font-semibold text-slate-800 mb-5">Ringkasan Build</h2>

            {{-- Daftar komponen --}}
            <div class="space-y-3 text-sm">
                @foreach([
                    'motherboard' => 'Motherboard',
                    'cpu'         => 'Processor',
                    'ram'         => 'RAM',
                    'vga'         => 'VGA',
                    'storage'     => 'Storage',
                    'psu'         => 'PSU',
                ] as $key => $label)
                    <div class="flex justify-between items-start gap-2" id="summary-row-{{ $key }}">
                        <span class="text-slate-500 flex-shrink-0">{{ $label }}</span>
                        <div class="text-right">
                            <span id="summary-price-{{ $key }}" class="text-slate-300 text-sm">—</span>
                            <p id="summary-name-{{ $key }}" class="text-xs text-slate-400 hidden mt-0.5 leading-tight"></p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Estimasi daya --}}
            <div class="mt-5 bg-slate-50 rounded-xl p-4 text-sm">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-slate-500 font-medium">Estimasi Daya</span>
                    <span class="font-semibold text-slate-700" id="summary-watt">— W</span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed" id="summary-psu-rec">
                    Pilih CPU & VGA untuk estimasi daya
                </p>
            </div>

            {{-- Warning PSU tidak cukup --}}
            <div id="psu-warning"
                 class="hidden mt-3 bg-red-50 border border-red-100 rounded-xl p-3 text-xs text-red-600 leading-relaxed">
            </div>

            {{-- Warning kompatibilitas inline di summary --}}
            <div id="compat-warning"
                 class="hidden mt-3 bg-amber-50 border border-amber-100 rounded-xl p-3 text-xs text-amber-700 leading-relaxed">
            </div>

            {{-- Total harga --}}
            <div class="mt-5 pt-4 border-t border-slate-100">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-600">Total Estimasi</span>
                    <span id="summary-total" class="text-lg font-bold text-slate-800">Rp 0</span>
                </div>
            </div>

            {{-- Tombol Cetak --}}
            <button onclick="saveBuild()"
                class="mt-4 w-full py-3 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-700 transition">
                Cetak Estimasi
            </button>
        </div>

    </div>
</div>

{{-- ==========================================================================
     MODAL PILIH KOMPONEN
     Nama fungsi pakai openComponentModal / closeComponentModal (bukan openModal/
     closeModal) supaya tidak konflik dengan fungsi modal logout di layouts/app.
========================================================================== --}}
<div id="modal-overlay"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col">

        {{-- Header Modal --}}
        <div class="flex items-start justify-between p-6 border-b border-slate-100 gap-4">
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-semibold text-slate-800" id="modal-title">Pilih Komponen</h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed" id="modal-compat-info"></p>
            </div>
            <button onclick="closeComponentModal()"
                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition text-lg">
                ×
            </button>
        </div>

        {{-- Search bar --}}
        <div class="px-4 pt-4 pb-2">
            <input type="text"
                id="modal-search"
                placeholder="Cari produk..."
                oninput="filterModalList(this.value)"
                class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 transition">
        </div>

        {{-- Loading state --}}
        <div id="modal-loading" class="flex items-center justify-center py-16 hidden">
            <div class="text-center">
                <div class="w-8 h-8 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-sm text-slate-500">Memuat produk yang kompatibel...</p>
            </div>
        </div>

        {{-- Empty state --}}
        <div id="modal-empty" class="flex items-center justify-center py-16 hidden">
            <div class="text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                    <span class="text-2xl">📦</span>
                </div>
                <p class="text-slate-600 text-sm font-medium">Tidak ada produk tersedia</p>
                <p class="text-slate-400 text-xs mt-1">Pastikan stok ada dan spec sudah diisi dengan benar</p>
            </div>
        </div>

        {{-- Product list --}}
        <div id="modal-list" class="overflow-y-auto flex-1 px-4 pb-4 space-y-2 mt-2"></div>

    </div>
</div>

<script>
// =============================================================================
// STATE — semua komponen yang sudah dipilih
// =============================================================================
const build = {
    motherboard: null,
    cpu:         null,
    ram:         null,
    vga:         null,
    storage:     null,
    psu:         null,
};

let currentModalKey  = null;
let allModalProducts = [];  // simpan semua produk modal untuk fitur search

// =============================================================================
// FILTER PARAMS
//
// Satu fungsi terpusat yang menentukan filter apa yang dikirim ke server.
// Key yang dikirim HARUS cocok dengan yang diterima PcBuilderController::getCompatible().
//
// Mapping:
//   CPU/CPU Cooler → socket_type dari mobo
//   RAM            → ram_type dari mobo.ram_type_slot
//   PSU            → min_wattage dihitung dari cpu.tdp_watt + vga.min_psu_watt
// =============================================================================
function buildFilterParams(key, type) {
    const params = new URLSearchParams({ type });

    switch (key) {
        case 'cpu':
        case 'cpu_cooler':
            // Kirim socket_type Motherboard → controller filter Processor by socket_type
            if (build.motherboard?.socket_type) {
                params.set('socket_type', build.motherboard.socket_type);
            }
            break;

        case 'ram':
            // Kirim ram_type_slot dari Motherboard sebagai param 'ram_type'
            // Controller akan filter RAM by spec_key='ram_type' dengan value ini
            // Mobo punya: ram_type_slot = 'DDR5'
            // RAM punya:  ram_type      = 'DDR5'  ← yang dicocokkan
            if (build.motherboard?.ram_type_slot) {
                params.set('ram_type', build.motherboard.ram_type_slot);
            }
            break;

        case 'psu':
            // Estimasi kebutuhan daya = (cpu_tdp + gpu_min_psu) * 1.3, bulatkan ke 50W
            const cpuTdp    = build.cpu?.tdp_watt    || 0;
            const gpuMinPsu = build.vga?.min_psu_watt || 0;
            const totalNeed = cpuTdp + gpuMinPsu;

            if (totalNeed > 0) {
                const minWattage = Math.ceil((totalNeed * 1.3) / 50) * 50;
                params.set('min_wattage', minWattage);
            }
            break;
    }

    return params;
}

// =============================================================================
// COMPAT INFO TEXT — teks hint yang ditampilkan di header modal
// =============================================================================
function getCompatInfoText(key) {
    switch (key) {
        case 'cpu':
            return build.motherboard?.socket_type
                ? `Menampilkan Processor dengan socket ${build.motherboard.socket_type}`
                : 'Semua Processor ditampilkan';

        case 'ram':
            return build.motherboard?.ram_type_slot
                ? `Menampilkan RAM tipe ${build.motherboard.ram_type_slot} sesuai slot Motherboard`
                : 'Semua RAM ditampilkan';

        case 'psu': {
            const cpuTdp    = build.cpu?.tdp_watt    || 0;
            const gpuMinPsu = build.vga?.min_psu_watt || 0;
            if (cpuTdp || gpuMinPsu) {
                const minW = Math.ceil(((cpuTdp + gpuMinPsu) * 1.3) / 50) * 50;
                return `Estimasi kebutuhan: CPU ${cpuTdp}W + GPU ${gpuMinPsu}W → filter PSU ≥ ${minW}W`;
            }
            return 'Semua PSU ditampilkan';
        }

        default:
            return '';
    }
}

// =============================================================================
// OPEN COMPONENT MODAL
// =============================================================================
async function openComponentModal(type, key) {
    currentModalKey  = key;
    allModalProducts = [];

    const overlay = document.getElementById('modal-overlay');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');

    // Reset state modal
    document.getElementById('modal-title').textContent   = 'Pilih ' + type;
    document.getElementById('modal-compat-info').textContent = getCompatInfoText(key);
    document.getElementById('modal-search').value        = '';
    document.getElementById('modal-list').innerHTML      = '';
    document.getElementById('modal-empty').classList.add('hidden');
    document.getElementById('modal-loading').classList.remove('hidden');

    const params = buildFilterParams(key, type);

    try {
        const res = await fetch(`/pc-builder/compatible?${params}`);

        if (!res.ok) {
            throw new Error(`Server error ${res.status}`);
        }

        const products = await res.json();

        document.getElementById('modal-loading').classList.add('hidden');

        if (!products.length) {
            document.getElementById('modal-empty').classList.remove('hidden');
            return;
        }

        allModalProducts = products;
        renderModalProducts(products, key);

    } catch (err) {
        console.error('[PC Builder] Fetch error:', err);
        document.getElementById('modal-loading').classList.add('hidden');
        document.getElementById('modal-empty').classList.remove('hidden');
    }
}

// =============================================================================
// RENDER DAFTAR PRODUK DI MODAL
// =============================================================================
function renderModalProducts(products, key) {
    const list = document.getElementById('modal-list');
    list.innerHTML = '';

    if (!products.length) {
        document.getElementById('modal-empty').classList.remove('hidden');
        return;
    }

    document.getElementById('modal-empty').classList.add('hidden');

    products.forEach(p => {
        const isSelected = build[key]?.id === p.id;

        const div = document.createElement('div');
        div.className = [
            'p-4 rounded-xl border cursor-pointer transition-all',
            isSelected
                ? 'border-slate-900 bg-slate-50 ring-1 ring-slate-900'
                : 'border-slate-200 hover:border-slate-400 hover:bg-slate-50',
        ].join(' ');

        div.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        ${p.brand ? `<span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-medium flex-shrink-0">${escapeHtml(p.brand)}</span>` : ''}
                        ${isSelected ? '<span class="text-xs bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded font-medium">✓ Dipilih</span>' : ''}
                    </div>
                    <p class="font-medium text-slate-800 text-sm leading-snug">${escapeHtml(p.name)}</p>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">${formatSpecsDisplay(p.specs)}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="font-bold text-slate-800 text-sm">${p.price_fmt}</p>
                </div>
            </div>
        `;

        div.dataset.name = p.name.toLowerCase();
        div.addEventListener('click', () => selectComponent(key, p));
        list.appendChild(div);
    });
}

// Format specs jadi string singkat
// Prioritaskan canonical key penting di urutan pertama
function formatSpecsDisplay(specs) {
    if (!specs || typeof specs !== 'object') return '—';

    const PRIORITY = [
        'socket_type', 'ram_type', 'ram_type_slot', 'form_factor',
        'tdp_watt', 'total_wattage', 'min_psu_watt', 'capacity_gb', 'speed_mhz',
    ];

    const allEntries = [
        ...PRIORITY.filter(k => specs[k]).map(k => [k, specs[k]]),
        ...Object.entries(specs).filter(([k]) => !PRIORITY.includes(k)),
    ].slice(0, 5);

    return allEntries
        .map(([k, v]) => `<span class="font-medium text-slate-500">${k.replace(/_/g, ' ')}:</span> ${escapeHtml(String(v))}`)
        .join(' &nbsp;·&nbsp; ');
}

// Search / filter di dalam modal
function filterModalList(query) {
    if (!allModalProducts.length) return;

    const q = query.toLowerCase().trim();

    const filtered = q
        ? allModalProducts.filter(p =>
            p.name.toLowerCase().includes(q) ||
            (p.brand || '').toLowerCase().includes(q) ||
            Object.values(p.specs || {}).some(v =>
                String(v).toLowerCase().includes(q)
            )
          )
        : allModalProducts;

    renderModalProducts(filtered, currentModalKey);
}

// Escape HTML untuk mencegah XSS
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// =============================================================================
// CLOSE MODAL
// =============================================================================
function closeComponentModal() {
    const overlay = document.getElementById('modal-overlay');
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    currentModalKey  = null;
    allModalProducts = [];
}

// =============================================================================
// SELECT COMPONENT — dipanggil saat user klik produk di modal
// =============================================================================
function selectComponent(key, product) {
    const prevProduct = build[key];
    build[key] = product;

    closeComponentModal();

    // Kalau Motherboard diganti, reset komponen yang bergantung
    if (key === 'motherboard' && prevProduct && prevProduct.id !== product.id) {
        resetDependents(['cpu', 'ram', 'vga', 'psu', 'storage']);
    }

    // Kalau CPU diganti, reset VGA dan PSU
    if (key === 'cpu' && prevProduct && prevProduct.id !== product.id) {
        resetDependents(['psu']);
    }

    // Kalau VGA diganti, reset PSU
    if (key === 'vga' && prevProduct && prevProduct.id !== product.id) {
        resetDependents(['psu']);
    }

    updateRow(key, product);
    unlockNext(key);
    updateSummary();
    checkCompatibility();
}

// Reset komponen tertentu ke kondisi awal (ketika komponen yang jadi
// prasyaratnya diganti)
function resetDependents(keys) {
    keys.forEach(key => {
        if (!build[key]) return;

        build[key] = null;

        // Reset tampilan baris
        const nameEl  = document.getElementById(`name-${key}`);
        const priceEl = document.getElementById(`price-${key}`);
        const hintEl  = document.getElementById(`hint-${key}`);
        const iconEl  = document.getElementById(`icon-${key}`);
        const btnEl   = document.getElementById(`btn-${key}`);
        const rowEl   = document.getElementById(`row-${key}`);

        const PLACEHOLDERS = {
            cpu:     'Pilih Motherboard dulu',
            ram:     'Pilih Motherboard dulu',
            vga:     'Pilih CPU dulu',
            storage: 'Pilih Motherboard dulu',
            psu:     'Pilih CPU & VGA dulu',
        };

        if (nameEl)  { nameEl.textContent = PLACEHOLDERS[key] ?? 'Belum dipilih'; nameEl.classList.add('text-slate-400', 'italic'); nameEl.classList.remove('text-slate-800'); }
        if (priceEl) { priceEl.textContent = ''; priceEl.classList.add('hidden'); }
        if (hintEl)  { hintEl.textContent = ''; hintEl.classList.add('hidden'); }
        if (iconEl)  { iconEl.textContent = STEP_NUMBERS[key] ?? '?'; iconEl.classList.remove('bg-emerald-100', 'text-emerald-600'); iconEl.classList.add('bg-slate-100', 'text-slate-500'); }
        if (btnEl)   { btnEl.textContent = 'Pilih'; btnEl.disabled = true; btnEl.classList.remove('bg-slate-900', 'text-white', 'hover:bg-slate-700'); btnEl.classList.add('bg-slate-200', 'text-slate-400', 'cursor-not-allowed'); }
        if (rowEl)   { rowEl.classList.add('opacity-50'); }

        // Reset summary
        const summaryPrice = document.getElementById(`summary-price-${key}`);
        const summaryName  = document.getElementById(`summary-name-${key}`);
        if (summaryPrice) { summaryPrice.textContent = '—'; summaryPrice.classList.remove('text-slate-800', 'font-medium'); summaryPrice.classList.add('text-slate-300'); }
        if (summaryName)  { summaryName.textContent = ''; summaryName.classList.add('hidden'); }
    });
}

const STEP_NUMBERS = {
    motherboard: '1', cpu: '2', ram: '3', vga: '4', storage: '5', psu: '6',
};

// Update tampilan baris setelah komponen berhasil dipilih
function updateRow(key, product) {
    const nameEl  = document.getElementById(`name-${key}`);
    const priceEl = document.getElementById(`price-${key}`);
    const iconEl  = document.getElementById(`icon-${key}`);
    const rowEl   = document.getElementById(`row-${key}`);
    const btnEl   = document.getElementById(`btn-${key}`);

    if (nameEl) {
        nameEl.textContent = product.name;
        nameEl.classList.remove('text-slate-400', 'italic');
        nameEl.classList.add('text-slate-800');
    }

    if (priceEl) {
        priceEl.textContent = product.price_fmt;
        priceEl.classList.remove('hidden');
    }

    if (iconEl) {
        iconEl.textContent = '✓';
        iconEl.classList.remove('bg-slate-100', 'text-slate-500');
        iconEl.classList.add('bg-emerald-100', 'text-emerald-600');
    }

    if (rowEl) rowEl.classList.remove('opacity-50');

    if (btnEl) {
        btnEl.textContent = 'Ganti';
        btnEl.disabled    = false;
        btnEl.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
        btnEl.classList.add('bg-slate-900', 'text-white', 'hover:bg-slate-700');
    }
}

// =============================================================================
// UNLOCK NEXT — aktifkan baris komponen setelah prasyaratnya dipenuhi
//
//   Motherboard dipilih → CPU, RAM, Storage aktif
//   CPU dipilih         → VGA aktif
//   CPU + VGA dipilih   → PSU aktif
// =============================================================================
function unlockNext(key) {
    if (key === 'motherboard') {
        const socketHint = build.motherboard?.socket_type
            ? `Filter socket: ${build.motherboard.socket_type}`
            : 'Bebas pilih Processor';

        const ramHint = build.motherboard?.ram_type_slot
            ? `Filter RAM: ${build.motherboard.ram_type_slot}`
            : 'Bebas pilih RAM';

        enableRow('cpu',     socketHint,          'hint-cpu');
        enableRow('ram',     ramHint,              'hint-ram');
        enableRow('storage', 'Bebas pilih Storage', null);
    }

    if (key === 'cpu') {
        enableRow('vga', 'Bebas pilih VGA', null);
    }

    if ((key === 'cpu' || key === 'vga') && build.cpu && build.vga) {
        const cpuTdp    = build.cpu?.tdp_watt    || 0;
        const gpuMinPsu = build.vga?.min_psu_watt || 0;
        const minW      = Math.ceil(((cpuTdp + gpuMinPsu) * 1.3) / 50) * 50;

        enableRow('psu', `Pilih`, `hint-psu`);

        const hintPsu = document.getElementById('hint-psu');
        if (hintPsu) {
            hintPsu.textContent = `Filter PSU ≥ ${minW}W (CPU ${cpuTdp}W + GPU ${gpuMinPsu}W + headroom 30%)`;
            hintPsu.classList.remove('hidden');
        }
    }
}

function enableRow(key, placeholder, hintId) {
    const row  = document.getElementById(`row-${key}`);
    const btn  = document.getElementById(`btn-${key}`);
    const name = document.getElementById(`name-${key}`);
    const hint = hintId ? document.getElementById(hintId) : null;

    if (row)  row.classList.remove('opacity-50');

    if (btn) {
        btn.disabled = false;
        btn.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
        btn.classList.add('bg-slate-900', 'text-white', 'hover:bg-slate-700');
    }

    if (name && !build[key]) {
        name.textContent = placeholder;
        name.classList.add('text-slate-400', 'italic');
        name.classList.remove('text-slate-800');
    }

    if (hint && !build[key]) {
        // hint diisi oleh unlockNext setelah enableRow
    }
}

// =============================================================================
// UPDATE SUMMARY — hitung total harga + estimasi daya
// =============================================================================
function updateSummary() {
    let total = 0;

    Object.entries(build).forEach(([key, product]) => {
        const priceEl = document.getElementById(`summary-price-${key}`);
        const nameEl  = document.getElementById(`summary-name-${key}`);

        if (product) {
            if (priceEl) {
                priceEl.textContent = product.price_fmt;
                priceEl.classList.remove('text-slate-300');
                priceEl.classList.add('text-slate-800', 'font-medium');
            }
            if (nameEl) {
                nameEl.textContent = product.name;
                nameEl.classList.remove('hidden');
            }
            total += product.price;
        } else {
            if (priceEl) {
                priceEl.textContent = '—';
                priceEl.classList.remove('text-slate-800', 'font-medium');
                priceEl.classList.add('text-slate-300');
            }
            if (nameEl) {
                nameEl.textContent = '';
                nameEl.classList.add('hidden');
            }
        }
    });

    document.getElementById('summary-total').textContent =
        'Rp ' + total.toLocaleString('id-ID');

    // Estimasi daya
    const cpuTdp    = build.cpu?.tdp_watt    || 0;
    const gpuMinPsu = build.vga?.min_psu_watt || 0;
    const totalNeed = cpuTdp + gpuMinPsu;

    const wattEl  = document.getElementById('summary-watt');
    const recEl   = document.getElementById('summary-psu-rec');

    if (totalNeed > 0) {
        const recommended = Math.ceil((totalNeed * 1.3) / 50) * 50;
        wattEl.textContent = `${totalNeed} W`;
        recEl.textContent  = `Rekomendasi PSU minimal ${recommended}W`;
    } else {
        wattEl.textContent = '— W';
        recEl.textContent  = 'Pilih CPU & VGA untuk estimasi daya';
    }

    // Validasi wattage PSU yang dipilih
    validatePsuWattage();
}

function validatePsuWattage() {
    const warningEl = document.getElementById('psu-warning');

    if (!build.psu || (!build.cpu && !build.vga)) {
        warningEl.classList.add('hidden');
        return;
    }

    const cpuTdp      = build.cpu?.tdp_watt     || 0;
    const gpuMinPsu   = build.vga?.min_psu_watt  || 0;
    const totalNeed   = cpuTdp + gpuMinPsu;
    const recommended = Math.ceil((totalNeed * 1.3) / 50) * 50;
    const psuWattage  = build.psu?.total_wattage || 0;

    if (psuWattage > 0 && psuWattage < recommended) {
        warningEl.textContent = `⚠ PSU ${psuWattage}W mungkin tidak cukup untuk build ini. Direkomendasikan ≥ ${recommended}W.`;
        warningEl.classList.remove('hidden');
    } else {
        warningEl.classList.add('hidden');
    }
}

// =============================================================================
// CEK KOMPATIBILITAS
//
// Dijalankan setiap kali komponen dipilih atau diganti.
// Menggunakan canonical key yang konsisten dengan data dari server.
//
// Cek yang dilakukan:
//   1. socket_type CPU harus cocok dengan socket_type Motherboard
//   2. ram_type RAM harus cocok dengan ram_type_slot Motherboard
// =============================================================================
function checkCompatibility() {
    const statusEl  = document.getElementById('compat-status');
    const warningEl = document.getElementById('compat-warning');

    // --- CEK 1: Socket CPU vs Motherboard ---
    if (build.cpu && build.motherboard) {
        const cpuSocket  = build.cpu.socket_type;
        const moboSocket = build.motherboard.socket_type;

        if (cpuSocket && moboSocket && cpuSocket !== moboSocket) {
            showCompatError(
                statusEl,
                '⚠ Tidak Kompatibel: Socket tidak cocok',
                `CPU membutuhkan socket ${cpuSocket}, Motherboard punya socket ${moboSocket}`
            );
            warningEl.textContent = `Socket tidak cocok: CPU (${cpuSocket}) ≠ Motherboard (${moboSocket})`;
            warningEl.classList.remove('hidden');
            return;
        }
    }

    // --- CEK 2: RAM type vs Motherboard slot ---
    if (build.ram && build.motherboard) {
        const moboRamSlot = build.motherboard.ram_type_slot;  // dari Motherboard
        const ramType     = build.ram.ram_type;               // dari RAM

        if (moboRamSlot && ramType && moboRamSlot !== ramType) {
            showCompatError(
                statusEl,
                '⚠ Tidak Kompatibel: Tipe RAM tidak cocok',
                `Slot Motherboard: ${moboRamSlot} — RAM yang dipilih: ${ramType}`
            );
            warningEl.textContent = `Tipe RAM tidak cocok: Slot Motherboard (${moboRamSlot}) ≠ RAM (${ramType})`;
            warningEl.classList.remove('hidden');
            return;
        }
    }

    // Semua kompatibel
    warningEl.classList.add('hidden');

    const filled = Object.values(build).filter(Boolean).length;

    if (filled >= 2) {
        statusEl.className   = 'rounded-2xl p-4 bg-emerald-50 border border-emerald-100';
        statusEl.innerHTML   = `
            <p class="text-sm font-medium text-emerald-700">✓ Semua komponen yang dipilih kompatibel</p>
            <p class="text-xs text-emerald-600 mt-0.5">${filled} dari 6 komponen sudah dipilih</p>
        `;
        statusEl.classList.remove('hidden');
    } else {
        statusEl.classList.add('hidden');
    }
}

function showCompatError(el, title, detail) {
    el.className = 'rounded-2xl p-4 bg-red-50 border border-red-100';
    el.innerHTML = `
        <p class="text-sm font-medium text-red-700">${title}</p>
        <p class="text-xs text-red-600 mt-0.5">${detail}</p>
    `;
    el.classList.remove('hidden');
}

// =============================================================================
// RESET BUILD
// =============================================================================
function resetBuild() {
    if (!confirm('Reset semua pilihan komponen?')) return;
    Object.keys(build).forEach(k => build[k] = null);
    location.reload();
}

// =============================================================================
// SAVE BUILD — Cetak Estimasi
// =============================================================================
function saveBuild() {
    const filled = Object.entries(build).filter(([, v]) => v !== null);

    if (filled.length === 0) {
        alert('Pilih minimal satu komponen terlebih dahulu.');
        return;
    }

    const LABELS = {
        motherboard: 'Motherboard',
        cpu:         'Processor (CPU)',
        ram:         'RAM',
        vga:         'VGA / GPU',
        storage:     'Storage',
        psu:         'Power Supply',
    };

    const cpuTdp      = build.cpu?.tdp_watt    || 0;
    const gpuMinPsu   = build.vga?.min_psu_watt || 0;
    const totalNeed   = cpuTdp + gpuMinPsu;
    const recommended = totalNeed > 0 ? Math.ceil((totalNeed * 1.3) / 50) * 50 : null;
    const psuWattage  = build.psu?.total_wattage || 0;

    let total = 0;
    Object.values(build).forEach(p => { if (p) total += p.price; });

    // Bangun baris komponen
    const rows = Object.entries(LABELS).map(([key, label]) => {
        const p = build[key];
        if (!p) return `
            <tr>
                <td class="label">${label}</td>
                <td class="name" colspan="2" style="color:#94a3b8;font-style:italic;">Belum dipilih</td>
            </tr>`;

        // Specs penting untuk ditampilkan
        const specStr = p.specs
            ? Object.entries(p.specs).slice(0, 4)
                .map(([k, v]) => `${k.replace(/_/g, ' ')}: ${v}`)
                .join(' · ')
            : '';

        return `
            <tr>
                <td class="label">${label}</td>
                <td class="name">
                    ${p.brand ? `<span class="badge">${p.brand}</span> ` : ''}
                    ${p.name}
                    ${specStr ? `<br><span class="specs">${specStr}</span>` : ''}
                </td>
                <td class="price">${p.price_fmt}</td>
            </tr>`;
    }).join('');

    // Peringatan kompatibilitas
    let compatWarning = '';
    if (build.cpu && build.motherboard && build.cpu.socket_type && build.motherboard.socket_type
        && build.cpu.socket_type !== build.motherboard.socket_type) {
        compatWarning += `<div class="warning">⚠ Socket tidak cocok: CPU (${build.cpu.socket_type}) ≠ Motherboard (${build.motherboard.socket_type})</div>`;
    }
    if (build.ram && build.motherboard && build.ram.ram_type && build.motherboard.ram_type_slot
        && build.ram.ram_type !== build.motherboard.ram_type_slot) {
        compatWarning += `<div class="warning">⚠ Tipe RAM tidak cocok: Slot Motherboard (${build.motherboard.ram_type_slot}) ≠ RAM (${build.ram.ram_type})</div>`;
    }
    if (psuWattage > 0 && recommended && psuWattage < recommended) {
        compatWarning += `<div class="warning">⚠ PSU ${psuWattage}W mungkin tidak cukup. Direkomendasikan ≥ ${recommended}W.</div>`;
    }

    const printDate = new Date().toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });

    const html = `<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Estimasi Rakit PC</title>
        <style>
            @page {
                margin: 0;         
                size: A4 portrait;
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 13px;
                color: #1e293b;
                padding: 32px;
                background: #fff;
            }
            .header {
                border-bottom: 2px solid #0f172a;
                padding-bottom: 14px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
            }
            .header h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
            .header p  { font-size: 11px; color: #64748b; margin-top: 3px; }
            .date       { font-size: 11px; color: #64748b; text-align: right; }

            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            thead tr { background: #0f172a; color: #fff; }
            thead th { padding: 9px 12px; text-align: left; font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; }
            tbody tr { border-bottom: 1px solid #e2e8f0; }
            tbody tr:last-child { border-bottom: none; }
            td { padding: 10px 12px; vertical-align: top; }

            td.label { font-weight: 600; color: #475569; width: 160px; white-space: nowrap; }
            td.name  { color: #1e293b; line-height: 1.5; }
            td.price { text-align: right; font-weight: 600; white-space: nowrap; color: #0f172a; width: 130px; }

            .badge { display: inline-block; font-size: 10px; background: #f1f5f9; color: #64748b; padding: 1px 6px; border-radius: 4px; font-weight: 600; margin-right: 4px; }
            .specs { font-size: 10px; color: #94a3b8; }

            .total-row { background: #f8fafc; }
            .total-row td { padding: 12px; font-weight: 700; font-size: 14px; }

            .section {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 14px 16px;
                margin-bottom: 14px;
            }
            .section-title { font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: #64748b; margin-bottom: 8px; }
            .watt-info { display: flex; justify-content: space-between; font-size: 12px; color: #334155; }
            .watt-info span { font-weight: 600; }

            .warning {
                background: #fff7ed;
                border: 1px solid #fed7aa;
                border-radius: 6px;
                padding: 8px 12px;
                margin-bottom: 8px;
                color: #c2410c;
                font-size: 11px;
            }

            .footer {
                margin-top: 28px;
                border-top: 1px solid #e2e8f0;
                padding-top: 12px;
                font-size: 10px;
                color: #94a3b8;
                text-align: center;
            }

            @media print {
                body { padding: 20px; }
                button { display: none !important; }
            }
        </style>
    </head>
    <body>

        <div class="header">
            <div>
                <h1>Estimasi Rakit PC</h1>
                <p>Simulasi konfigurasi dan kompatibilitas komponen PC</p>
            </div>
            <div class="date">Dicetak: ${printDate}</div>
        </div>

        ${compatWarning}

        <table>
            <thead>
                <tr>
                    <th>Komponen</th>
                    <th>Produk</th>
                    <th style="text-align:right">Harga</th>
                </tr>
            </thead>
            <tbody>
                ${rows}
                <tr class="total-row">
                    <td colspan="2">Total Estimasi</td>
                    <td class="price" style="font-size:15px;">Rp ${total.toLocaleString('id-ID')}</td>
                </tr>
            </tbody>
        </table>

        ${totalNeed > 0 ? `
        <div class="section">
            <div class="section-title">Estimasi Konsumsi Daya</div>
            <div class="watt-info">
                <span>CPU TDP</span><span>${cpuTdp} W</span>
            </div>
            <div class="watt-info">
                <span>GPU Min PSU</span><span>${gpuMinPsu} W</span>
            </div>
            <div class="watt-info" style="margin-top:6px;padding-top:6px;border-top:1px solid #e2e8f0;">
                <span>Total Estimasi</span><span>${totalNeed} W</span>
            </div>
            <div class="watt-info">
                <span>Rekomendasi PSU (headroom 30%)</span>
                <span style="color:#0f172a;">≥ ${recommended} W</span>
            </div>
            ${psuWattage ? `<div class="watt-info"><span>PSU Dipilih</span><span>${psuWattage} W</span></div>` : ''}
        </div>` : ''}

        <div class="footer">
            Dokumen ini merupakan estimasi. Harga dapat berubah sewaktu-waktu.
        </div>

        <script>window.onload = () => { window.print(); }<\/script>
    </body>
    </html>`;

    const w = window.open('', '_blank', 'width=800,height=700');
    w.document.write(html);
    w.document.close();
}

// =============================================================================
// EVENT LISTENERS
// =============================================================================
document.addEventListener('DOMContentLoaded', function () {
    // Tutup modal jika klik di backdrop
    document.getElementById('modal-overlay').addEventListener('click', function (e) {
        if (e.target === this) closeComponentModal();
    });

    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && currentModalKey !== null) {
            closeComponentModal();
        }
    });
});
</script>
@endsection
