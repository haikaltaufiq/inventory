@extends('layouts.app')

@section('title', 'Simulasi Rakit PC')

@section('content')
    @php
        $pcBuilderComponents = [
            ['key' => 'cpu', 'label' => 'Processor', 'type' => 'Processor'],
            ['key' => 'motherboard', 'label' => 'Motherboard', 'type' => 'Motherboard'],
            ['key' => 'ram', 'label' => 'RAM', 'type' => 'RAM'],
            ['key' => 'casing', 'label' => 'Casing', 'type' => 'Casing'],
            ['key' => 'psu', 'label' => 'Power Supply', 'type' => 'Power Supply'],
            ['key' => 'harddisk', 'label' => 'Hardisk', 'type' => 'Storage', 'storage_filter' => 'hdd'],
            ['key' => 'ssd', 'label' => 'SSD Sata / NVMe', 'type' => 'Storage', 'storage_filter' => 'ssd'],
            ['key' => 'vga', 'label' => 'Graphic Cards', 'type' => 'VGA'],
            ['key' => 'assembly', 'label' => 'Jasa Rakit PC', 'type' => 'Jasa Rakit PC'],
            ['key' => 'monitor', 'label' => 'Monitor', 'type' => 'Monitor'],
            ['key' => 'cpu_cooler', 'label' => 'Cooler CPU', 'type' => 'CPU Cooler'],
            ['key' => 'case_fan', 'label' => 'Fan Casing', 'type' => 'Fan Casing'],
            ['key' => 'os', 'label' => 'Operating System', 'type' => 'Operating System'],
            ['key' => 'mouse', 'label' => 'Mouse', 'type' => 'Mouse'],
            ['key' => 'mousepad', 'label' => 'Mousepad', 'type' => 'Mousepad'],
            ['key' => 'keyboard', 'label' => 'Keyboard', 'type' => 'Keyboard'],
            ['key' => 'headset', 'label' => 'Headset', 'type' => 'Headset'],
            ['key' => 'webcam', 'label' => 'Webcam', 'type' => 'Webcam'],
            ['key' => 'networking', 'label' => 'Networking', 'type' => 'Networking (Wifi/LAN)'],
            ['key' => 'ups', 'label' => 'UPS', 'type' => 'UPS'],
        ];

        $componentGroups = [
            'main' => [
                'label' => 'KOMPONEN UTAMA',
                'keys' => ['motherboard', 'cpu', 'ram', 'casing', 'psu', 'harddisk', 'ssd', 'vga'],
                'color' => 'bg-blue-50 border-blue-200',
            ],
            'cooling' => [
                'label' => 'AKSESORIS & PENDINGIN',
                'keys' => ['assembly', 'cpu_cooler', 'case_fan'],
                'color' => 'bg-purple-50 border-purple-200',
            ],
            'peripherals' => [
                'label' => 'PERIHAL LENGKAP',
                'keys' => ['monitor', 'os', 'keyboard', 'mouse', 'mousepad', 'headset', 'webcam', 'networking', 'ups'],
                'color' => 'bg-orange-50 border-orange-200',
            ],
        ];

        $componentsByGroup = [];
        foreach ($componentGroups as $groupKey => $group) {
            $componentsByGroup[$groupKey] = array_filter(
                $pcBuilderComponents,
                fn($comp) => in_array($comp['key'], $group['keys']),
            );
        }
    @endphp
    <div class="px-5 pb-10">
        <div id="success-toast"
            class="fixed left-1/2 top-6 z-[70] hidden w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 shadow-2xl ring-1 ring-emerald-100">
        </div>

        {{-- ===================================================================
         HEADER
    =================================================================== --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-800">Simulasi Rakit PC</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Pilih komponen dari mana saja. Pilihan berikutnya otomatis mengikuti spesifikasi yang sudah
                    dipilih.
                </p>
            </div>
            <button onclick="resetBuild()"
                class="px-4 py-2 rounded-xl bg-slate-100 text-sm font-medium text-slate-600 hover:bg-slate-200 transition">
                Reset Build
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ===============================================================
              KIRI: COMPONENT SELECTOR WITH GROUPING
              Dependency chain:
                Motherboard → selalu aktif (entry point)
                CPU         → aktif setelah Mobo; difilter by socket_type
                RAM         → aktif setelah Mobo; difilter by ram_type_slot → ram_type
                VGA         → aktif setelah CPU
                Storage     → aktif setelah Mobo (bebas pilih)
                PSU         → aktif setelah CPU + VGA; difilter by min_wattage
         =============================================================== --}}
            <div class="lg:col-span-2 space-y-5">

                @foreach ($componentGroups as $groupKey => $group)
                    <div class="space-y-3">
                        {{-- Group Header --}}
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-600 px-1">
                            {{ $group['label'] }}
                        </h3>

                        {{-- Components in group --}}
                        <div class="space-y-3">
                            @foreach ($componentsByGroup[$groupKey] as $component)
                                <div class="bg-white rounded-xl shadow-sm p-4 transition-opacity duration-300"
                                    id="row-{{ $component['key'] }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <div id="icon-{{ $component['key'] }}"
                                                class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                                                ●
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">
                                                    {{ $component['label'] }}</p>
                                                <p id="name-{{ $component['key'] }}"
                                                    class="font-medium text-slate-400 italic text-sm truncate">Belum dipilih
                                                </p>
                                                <p id="price-{{ $component['key'] }}"
                                                    class="text-xs text-emerald-600 font-medium mt-0.5 hidden"></p>
                                                <p id="hint-{{ $component['key'] }}"
                                                    class="text-xs text-blue-500 mt-0.5 hidden"></p>
                                            </div>
                                        </div>
                                        <button
                                            onclick="openComponentModal('{{ $component['type'] }}', '{{ $component['key'] }}')"
                                            id="btn-{{ $component['key'] }}"
                                            class="flex-shrink-0 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs hover:bg-slate-700 transition font-medium">
                                            Pilih
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- STATUS KOMPATIBILITAS --}}
                <div id="compat-status" class="hidden rounded-2xl p-5 transition-all"></div>

            </div>

            {{-- ===============================================================
             KANAN: BUILD SUMMARY
        =============================================================== --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col h-fit sticky top-5">
                <h2 class="text-base font-semibold text-slate-800 mb-5">Ringkasan Build</h2>

                {{-- Daftar komponen --}}
                <div id="summary-selected-list" class="space-y-3 text-sm">
                    <p id="summary-empty" class="text-sm text-slate-400 italic">Belum ada item dipilih.</p>
                    {{--
                @foreach ($pcBuilderComponents as $component)
                    <div class="flex justify-between items-start gap-2" id="summary-row-{{ $component['key'] }}">
                        <span class="text-slate-500 flex-shrink-0">{{ $component['label'] }}</span>
                        <div class="text-right">
                            <span id="summary-price-{{ $component['key'] }}" class="text-slate-300 text-sm">—</span>
                            <p id="summary-name-{{ $component['key'] }}" class="text-xs text-slate-400 hidden mt-0.5 leading-tight"></p>
                        </div>
                    </div>
                @endforeach
                --}}
                </div>

                {{-- Estimasi daya --}}
                <div class="mt-5 bg-slate-50 rounded-xl p-4 text-sm">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-slate-500 font-medium">Estimasi Daya</span>
                        <span class="font-semibold text-slate-700" id="summary-watt">— W</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed" id="summary-psu-rec">
                        Pilih CPU atau GPU untuk estimasi daya
                    </p>
                </div>

                {{-- Warning PSU --}}
                <div id="psu-warning"
                    class="hidden mt-3 bg-red-50 border border-red-100 rounded-xl p-3 text-xs text-red-600 leading-relaxed">
                </div>

                {{-- Warning kompatibilitas --}}
                <div id="compat-warning"
                    class="hidden mt-3 bg-amber-50 border border-amber-100 rounded-xl p-3 text-xs text-amber-700 leading-relaxed">
                </div>

                {{-- ============================================================
                TOTAL MODAL + MARGIN + HARGA JUAL
            ============================================================ --}}
                <div class="mt-5 pt-4 border-t border-slate-100 space-y-3">

                    {{-- Total Modal --}}
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Total Modal</span>
                        <span id="summary-total-modal" class="text-sm font-semibold text-slate-700">Rp 0</span>
                    </div>

                    {{-- Input Margin --}}
                    <div class="bg-slate-50 rounded-xl p-3">
                        <label class="text-xs text-slate-500 font-medium block mb-2">
                            Margin Keuntungan
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" id="input-margin" value="15" min="0" max="1000"
                                step="0.5" oninput="updateMargin()"
                                class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900/20 text-right font-medium">
                            <span class="text-sm text-slate-500 flex-shrink-0">%</span>
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-slate-400">
                            <span>Keuntungan</span>
                            <span id="summary-margin-amount" class="font-medium text-emerald-600">+ Rp 0</span>
                        </div>
                    </div>

                    {{-- Harga Jual Final --}}
                    <div class="flex justify-between items-center pt-1 border-t border-slate-100">
                        <span class="text-sm font-semibold text-slate-700">Harga Jual Set</span>
                        <span id="summary-total-jual" class="text-xl font-bold text-slate-900">Rp 0</span>
                    </div>

                </div>

                {{-- Tombol --}}
                <div class="mt-4 flex gap-2">
                    <button onclick="saveBuild()"
                        class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition">
                        Cetak Estimasi
                    </button>
                    <button onclick="openSaveBuildModal()"
                        class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-700 transition">
                        Simpan Build
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- ==========================================================================
     MODAL PILIH KOMPONEN
     Nama fungsi pakai openComponentModal / closeComponentModal (bukan openModal/
     closeModal) supaya tidak konflik dengan fungsi modal logout di layouts/app.
========================================================================== --}}
    <div id="modal-overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
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
                <input type="text" id="modal-search" placeholder="Cari produk..." oninput="filterModalList(this.value)"
                    class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 transition">
            </div>

            {{-- Loading state --}}
            <div id="modal-loading" class="flex items-center justify-center py-16 hidden">
                <div class="text-center">
                    <div
                        class="w-8 h-8 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto mb-3">
                    </div>
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

    {{-- Modal Simpan Build --}}
    <div id="modal-save-build"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-base font-semibold text-slate-800 mb-4">Simpan Build</h3>

            <div class="space-y-3">
                <div>
                    <label class="text-xs text-slate-500 font-medium">Nama Build</label>
                    <input type="text" id="save-build-name" placeholder="Contoh: Gaming PC Budi - Budget 15jt"
                        class="mt-1 w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20">
                </div>
                <div>
                    <label class="text-xs text-slate-500 font-medium">Catatan (opsional)</label>
                    <textarea id="save-build-notes" rows="2" placeholder="Catatan untuk client..."
                        class="mt-1 w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20"></textarea>
                </div>
            </div>

            <div class="flex gap-2 mt-5">
                <button onclick="closeSaveBuildModal()"
                    class="flex-1 py-2.5 rounded-xl bg-slate-100 text-sm text-slate-600 hover:bg-slate-200">
                    Batal
                </button>
                <button onclick="confirmSaveBuild()"
                    class="flex-1 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-700">
                    Simpan
                </button>
            </div>
        </div>
    </div>

    <script>
        // =============================================================================
        // STATE — semua komponen yang sudah dipilih
        // =============================================================================
        const COMPONENTS = @json($pcBuilderComponents);
        const COMPONENT_BY_KEY = Object.fromEntries(COMPONENTS.map(component => [component.key, component]));
        const TOTAL_COMPONENTS = COMPONENTS.length;
        const NO_IMAGE_URL = @json(asset('assets/no-image.svg'));

        const build = Object.fromEntries(COMPONENTS.map(component => [component.key, null]));
        const STEP_NUMBERS = Object.fromEntries(COMPONENTS.map((component, index) => [component.key, String(index + 1)]));
        const LABELS = Object.fromEntries(COMPONENTS.map(component => [component.key, component.label]));

        let currentModalKey = null;
        let allModalProducts = [];

        function getSpec(product, key) {
            return product?.[key] ?? product?.specs?.[key] ?? null;
        }

        function productQty(product) {
            const qty = parseInt(product?.qty, 10);
            return Number.isFinite(qty) && qty > 0 ? qty : 1;
        }

        function productLineTotal(product) {
            return (product?.price || 0) * productQty(product);
        }

        function intSpec(product, key) {
            const value = parseInt(getSpec(product, key), 10);
            return Number.isFinite(value) ? value : 0;
        }

        function normalizeValue(value) {
            return String(value || '').trim().toLowerCase();
        }

        function splitSpecValues(value) {
            return String(value || '')
                .split(/[\/,+|]/)
                .map(item => normalizeValue(item))
                .filter(Boolean);
        }

        function specSupports(containerValue, requiredValue) {
            if (!containerValue || !requiredValue) return true;

            const required = normalizeValue(requiredValue);
            const supported = splitSpecValues(containerValue);

            if (!supported.length) return normalizeValue(containerValue) === required;
            return supported.includes(required);
        }

        function getSelectedSocket(exceptKey = null) {
            const sources = ['cpu', 'motherboard', 'cpu_cooler'].filter(key => key !== exceptKey);
            return sources.map(key => getSpec(build[key], 'socket_type')).find(Boolean) || null;
        }

        function getSelectedRamType(exceptKey = null) {
            const moboRam = exceptKey !== 'motherboard' ? getSpec(build.motherboard, 'ram_type_slot') : null;
            const ramType = exceptKey !== 'ram' ? getSpec(build.ram, 'ram_type') : null;
            const cpuRam = exceptKey !== 'cpu' ? getSpec(build.cpu, 'ram_type_support') : null;

            if (moboRam) return moboRam;
            if (ramType) return ramType;
            if (cpuRam && !String(cpuRam).includes('/')) return cpuRam;
            return null;
        }

        function getRecommendedPsuWatt() {
            const cpuTdp = intSpec(build.cpu, 'tdp_watt');
            const gpuMinPsu = intSpec(build.vga, 'min_psu_watt');
            const totalNeed = cpuTdp + gpuMinPsu;

            return totalNeed > 0 ? Math.ceil((totalNeed * 1.3) / 50) * 50 : 0;
        }

        function buildFilterParams(key, type) {
            const params = new URLSearchParams({
                type
            });
            const socketType = getSelectedSocket(key);
            const ramType = getSelectedRamType(key);
            const minWattage = getRecommendedPsuWatt();

            if (socketType && ['cpu', 'motherboard', 'cpu_cooler'].includes(key)) {
                params.set('socket_type', socketType);
            }

            if (ramType && ['ram', 'motherboard'].includes(key)) {
                params.set('ram_type', ramType);
            }

            if (key === 'psu' && minWattage > 0) {
                params.set('min_wattage', minWattage);
            }

            return params;
        }

        function applyModalFilters(products, key) {
            return products.filter(product => {
                if (!matchesStorageFilter(product, key)) return false;
                return isProductCompatible(key, product, key);
            });
        }

        function matchesStorageFilter(product, key) {
            const filter = COMPONENT_BY_KEY[key]?.storage_filter;
            if (!filter) return true;

            const name = normalizeValue(product.name);
            const formFactor = normalizeValue(getSpec(product, 'form_factor'));
            const interfaceType = normalizeValue(getSpec(product, 'interface_type'));

            if (filter === 'hdd') {
                return name.includes('hdd') || formFactor === '3.5' || formFactor === '3.5 inch';
            }

            if (filter === 'ssd') {
                return interfaceType.includes('nvme') || interfaceType.includes('sata') || formFactor === 'm.2' ||
                    formFactor === '2.5' || name.includes('ssd');
            }

            return true;
        }

        function isProductCompatible(key, product, exceptKey = null) {
            if (!product) return true;

            const socketType = getSpec(product, 'socket_type');
            const selectedSocket = getSelectedSocket(exceptKey);
            if (socketType && selectedSocket && ['cpu', 'motherboard', 'cpu_cooler'].includes(key) && socketType !==
                selectedSocket) {
                return false;
            }

            if (key === 'ram') {
                const ramType = getSpec(product, 'ram_type');
                const moboRam = exceptKey !== 'motherboard' ? getSpec(build.motherboard, 'ram_type_slot') : null;
                const cpuRam = exceptKey !== 'cpu' ? getSpec(build.cpu, 'ram_type_support') : null;
                if (moboRam && ramType && ramType !== moboRam) return false;
                if (cpuRam && ramType && !specSupports(cpuRam, ramType)) return false;
            }

            if (key === 'motherboard') {
                const moboRam = getSpec(product, 'ram_type_slot');
                const selectedRam = exceptKey !== 'ram' ? getSpec(build.ram, 'ram_type') : null;
                if (moboRam && selectedRam && moboRam !== selectedRam) return false;
            }

            if (key === 'psu') {
                const recommended = getRecommendedPsuWatt();
                const wattage = intSpec(product, 'total_wattage');
                if (recommended > 0 && wattage > 0 && wattage < recommended) return false;
            }

            return matchesPhysicalFit(key, product, exceptKey);
        }

        function matchesPhysicalFit(key, product, exceptKey = null) {
            const casing = key === 'casing' ? product : build.casing;
            if (!casing) return true;

            const supportedMobo = getSpec(casing, 'supported_motherboard_sizes');
            const mobo = key === 'motherboard' ? product : build.motherboard;
            const moboFormFactor = getSpec(mobo, 'form_factor');
            if (supportedMobo && moboFormFactor && !specSupports(supportedMobo, moboFormFactor)) return false;

            const maxGpuLength = intSpec(casing, 'max_gpu_length_mm');
            const gpu = key === 'vga' ? product : build.vga;
            const gpuLength = intSpec(gpu, 'length_mm');
            if (maxGpuLength > 0 && gpuLength > 0 && gpuLength > maxGpuLength) return false;

            const maxCoolerHeight = intSpec(casing, 'max_cpu_cooler_height_mm');
            const cooler = key === 'cpu_cooler' ? product : build.cpu_cooler;
            const coolerHeight = intSpec(cooler, 'height_mm');
            if (maxCoolerHeight > 0 && coolerHeight > 0 && coolerHeight > maxCoolerHeight) return false;

            return true;
        }

        function getCompatInfoText(key) {
            const parts = [];
            const socketType = getSelectedSocket(key);
            const ramType = getSelectedRamType(key);
            const recommendedPsu = getRecommendedPsuWatt();

            if (socketType && ['cpu', 'motherboard', 'cpu_cooler'].includes(key)) parts.push(`socket ${socketType}`);
            if (ramType && ['ram', 'motherboard'].includes(key)) parts.push(`RAM ${ramType}`);
            if (key === 'psu' && recommendedPsu > 0) parts.push(`PSU minimal ${recommendedPsu}W`);
            if (COMPONENT_BY_KEY[key]?.storage_filter === 'hdd') parts.push('hanya Hardisk');
            if (COMPONENT_BY_KEY[key]?.storage_filter === 'ssd') parts.push('SSD SATA / NVMe');

            return parts.length ? `Menampilkan produk kompatibel: ${parts.join(', ')}` :
                'Semua produk stok tersedia ditampilkan';
        }

        async function openComponentModal(type, key) {
            currentModalKey = key;
            allModalProducts = [];

            const overlay = document.getElementById('modal-overlay');
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');

            document.getElementById('modal-title').textContent = 'Pilih ' + (LABELS[key] || type);
            document.getElementById('modal-compat-info').textContent = getCompatInfoText(key);
            document.getElementById('modal-search').value = '';
            document.getElementById('modal-list').innerHTML = '';
            document.getElementById('modal-empty').classList.add('hidden');
            document.getElementById('modal-loading').classList.remove('hidden');

            const params = buildFilterParams(key, type);

            try {
                const res = await fetch(`/pc-builder/compatible?${params}`);
                if (!res.ok) throw new Error(`Server error ${res.status}`);

                const products = applyModalFilters(await res.json(), key);
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
                    isSelected ? 'border-slate-900 bg-slate-50 ring-1 ring-slate-900' :
                    'border-slate-200 hover:border-slate-400 hover:bg-slate-50',
                ].join(' ');

                div.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-100">
                    <img src="${escapeHtml(p.image_url || NO_IMAGE_URL)}"
                        alt="${escapeHtml(p.name)}"
                        class="w-full h-full object-cover"
                        onerror="this.src='${NO_IMAGE_URL}'">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        ${p.brand ? `<span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-medium flex-shrink-0">${escapeHtml(p.brand)}</span>` : ''}
                        ${isSelected ? '<span class="text-xs bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded font-medium">Dipilih</span>' : ''}
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

        function formatSpecsDisplay(specs) {
            if (!specs || typeof specs !== 'object') return '-';

            const PRIORITY = [
                'socket_type', 'ram_type', 'ram_type_slot', 'ram_type_support', 'form_factor',
                'supported_motherboard_sizes', 'interface_type', 'capacity_gb', 'speed_mhz',
                'tdp_watt', 'total_wattage', 'min_psu_watt', 'length_mm', 'max_gpu_length_mm',
                'height_mm', 'max_cpu_cooler_height_mm',
            ];

            const allEntries = [
                ...PRIORITY.filter(k => specs[k]).map(k => [k, specs[k]]),
                ...Object.entries(specs).filter(([k]) => !PRIORITY.includes(k)),
            ].slice(0, 5);

            return allEntries
                .map(([k, v]) =>
                    `<span class="font-medium text-slate-500">${k.replace(/_/g, ' ')}:</span> ${escapeHtml(String(v))}`)
                .join(' &nbsp;-&nbsp; ');
        }

        function filterModalList(query) {
            if (!allModalProducts.length) return;

            const q = query.toLowerCase().trim();
            const filtered = q ?
                allModalProducts.filter(p =>
                    p.name.toLowerCase().includes(q) ||
                    (p.brand || '').toLowerCase().includes(q) ||
                    Object.values(p.specs || {}).some(v => String(v).toLowerCase().includes(q))
                ) :
                allModalProducts;

            renderModalProducts(filtered, currentModalKey);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function closeComponentModal() {
            const overlay = document.getElementById('modal-overlay');
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            currentModalKey = null;
            allModalProducts = [];
        }

        function selectComponent(key, product) {
            const currentQty = build[key]?.id === product.id ? productQty(build[key]) : 1;
            build[key] = {
                ...product,
                qty: currentQty
            };
            closeComponentModal();

            updateRow(key, product);
            clearInvalidSelections(key);
            refreshHints();
            updateSummary();
            checkCompatibility();
        }

        function clearInvalidSelections(changedKey) {
            Object.entries(build).forEach(([key, product]) => {
                if (!product || key === changedKey) return;
                if (!isProductCompatible(key, product, key)) {
                    build[key] = null;
                    resetRow(key);
                }
            });
        }

        function resetRow(key) {
            const nameEl = document.getElementById(`name-${key}`);
            const priceEl = document.getElementById(`price-${key}`);
            const hintEl = document.getElementById(`hint-${key}`);
            const iconEl = document.getElementById(`icon-${key}`);
            const btnEl = document.getElementById(`btn-${key}`);

            if (nameEl) {
                nameEl.textContent = 'Belum dipilih';
                nameEl.classList.add('text-slate-400', 'italic');
                nameEl.classList.remove('text-slate-800');
            }
            if (priceEl) {
                priceEl.textContent = '';
                priceEl.classList.add('hidden');
            }
            if (hintEl) {
                hintEl.textContent = '';
                hintEl.classList.add('hidden');
            }
            if (iconEl) {
                iconEl.textContent = STEP_NUMBERS[key] ?? '?';
                iconEl.classList.remove('bg-emerald-100', 'text-emerald-600');
                iconEl.classList.add('bg-slate-100', 'text-slate-500');
            }
            if (btnEl) {
                btnEl.textContent = 'Pilih';
                btnEl.disabled = false;
                btnEl.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
                btnEl.classList.add('bg-slate-900', 'text-white', 'hover:bg-slate-700');
            }
        }

        function updateRow(key, product) {
            const nameEl = document.getElementById(`name-${key}`);
            const priceEl = document.getElementById(`price-${key}`);
            const iconEl = document.getElementById(`icon-${key}`);
            const btnEl = document.getElementById(`btn-${key}`);

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
                iconEl.textContent = 'OK';
                iconEl.classList.remove('bg-slate-100', 'text-slate-500');
                iconEl.classList.add('bg-emerald-100', 'text-emerald-600');
            }

            if (btnEl) {
                btnEl.textContent = 'Ganti';
                btnEl.disabled = false;
                btnEl.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
                btnEl.classList.add('bg-slate-900', 'text-white', 'hover:bg-slate-700');
            }
        }

        function changeQty(key, delta) {
            if (!build[key]) return;

            const nextQty = productQty(build[key]) + delta;
            if (nextQty < 1) {
                removeComponent(key);
                return;
            }

            build[key].qty = nextQty;
            updateSummary();
        }

        function removeComponent(key) {
            if (!build[key]) return;

            build[key] = null;
            resetRow(key);
            refreshHints();
            updateSummary();
            checkCompatibility();
        }

        function refreshHints() {
            COMPONENTS.forEach(component => {
                const hintEl = document.getElementById(`hint-${component.key}`);
                if (!hintEl || build[component.key]) return;

                const text = getCompatInfoText(component.key);
                if (text && text !== 'Semua produk stok tersedia ditampilkan') {
                    hintEl.textContent = text;
                    hintEl.classList.remove('hidden');
                } else {
                    hintEl.textContent = '';
                    hintEl.classList.add('hidden');
                }
            });
        }

        function updateSummary() {
            let totalModal = 0;
            const selectedEntries = Object.entries(build).filter(([, product]) => product);
            const listEl = document.getElementById('summary-selected-list');

            selectedEntries.forEach(([, product]) => {
                totalModal += productLineTotal(product);
            });

            if (listEl) {
                if (!selectedEntries.length) {
                    listEl.innerHTML =
                        '<p id="summary-empty" class="text-sm text-slate-400 italic">Belum ada item dipilih.</p>';
                } else {
                    listEl.innerHTML = selectedEntries.map(([key, product]) => `
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3" id="summary-row-${key}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-medium">${escapeHtml(LABELS[key] || key)}</p>
                            <p class="text-sm font-medium text-slate-800 leading-snug mt-0.5">${escapeHtml(product.name)}</p>
                            <p class="text-xs text-emerald-600 font-medium mt-1">${product.price_fmt} x ${productQty(product)}</p>
                        </div>
                        <button type="button" onclick="removeComponent('${key}')"
                            class="w-7 h-7 rounded-lg bg-white text-slate-400 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition flex items-center justify-center"
                            aria-label="Hapus ${escapeHtml(LABELS[key] || key)}">
                            &times;
                        </button>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white overflow-hidden">
                            <button type="button" onclick="changeQty('${key}', -1)"
                                class="w-8 h-8 text-slate-500 hover:bg-slate-100 transition">-</button>
                            <span class="w-9 text-center text-sm font-semibold text-slate-700">${productQty(product)}</span>
                            <button type="button" onclick="changeQty('${key}', 1)"
                                class="w-8 h-8 text-slate-500 hover:bg-slate-100 transition">+</button>
                        </div>
                        <p class="text-sm font-semibold text-slate-800">Rp ${productLineTotal(product).toLocaleString('id-ID')}</p>
                    </div>
                </div>
            `).join('');
                }
            }

            window._totalModal = totalModal;
            document.getElementById('summary-total-modal').textContent = 'Rp ' + totalModal.toLocaleString('id-ID');
            updateMargin();

            const cpuTdp = intSpec(build.cpu, 'tdp_watt');
            const gpuMinPsu = intSpec(build.vga, 'min_psu_watt');
            const totalNeed = cpuTdp + gpuMinPsu;
            const wattEl = document.getElementById('summary-watt');
            const recEl = document.getElementById('summary-psu-rec');

            if (totalNeed > 0) {
                const recommended = getRecommendedPsuWatt();
                wattEl.textContent = `${totalNeed} W`;
                recEl.textContent = `Rekomendasi PSU minimal ${recommended}W`;
            } else {
                wattEl.textContent = '- W';
                recEl.textContent = 'Pilih CPU atau GPU untuk estimasi daya';
            }

            validatePsuWattage();
        }

        function updateMargin() {
            const totalModal = window._totalModal || 0;
            const marginPct = parseFloat(document.getElementById('input-margin')?.value) || 0;
            const marginAmount = Math.round(totalModal * marginPct / 100);
            const totalJual = totalModal + marginAmount;

            window._totalJual = totalJual;
            window._marginPct = marginPct;

            document.getElementById('summary-margin-amount').textContent = '+ Rp ' + marginAmount.toLocaleString('id-ID');
            document.getElementById('summary-total-jual').textContent = 'Rp ' + totalJual.toLocaleString('id-ID');
        }

        function validatePsuWattage() {
            const warningEl = document.getElementById('psu-warning');

            if (!build.psu || (!build.cpu && !build.vga)) {
                warningEl.classList.add('hidden');
                return;
            }

            const recommended = getRecommendedPsuWatt();
            const psuWattage = intSpec(build.psu, 'total_wattage');

            if (psuWattage > 0 && recommended > 0 && psuWattage < recommended) {
                warningEl.textContent =
                    `PSU ${psuWattage}W mungkin tidak cukup untuk build ini. Direkomendasikan minimal ${recommended}W.`;
                warningEl.classList.remove('hidden');
            } else {
                warningEl.classList.add('hidden');
            }
        }

        function getCompatibilityIssues() {
            const issues = [];

            if (build.cpu && build.motherboard) {
                const cpuSocket = getSpec(build.cpu, 'socket_type');
                const moboSocket = getSpec(build.motherboard, 'socket_type');
                if (cpuSocket && moboSocket && cpuSocket !== moboSocket) issues.push(
                    `Socket CPU (${cpuSocket}) tidak cocok dengan Motherboard (${moboSocket})`);
            }

            if (build.cpu_cooler && build.motherboard) {
                const coolerSocket = getSpec(build.cpu_cooler, 'socket_type');
                const moboSocket = getSpec(build.motherboard, 'socket_type');
                if (coolerSocket && moboSocket && coolerSocket !== moboSocket) issues.push(
                    `Socket Cooler (${coolerSocket}) tidak cocok dengan Motherboard (${moboSocket})`);
            }

            if (build.ram && build.motherboard) {
                const ramType = getSpec(build.ram, 'ram_type');
                const moboRam = getSpec(build.motherboard, 'ram_type_slot');
                if (ramType && moboRam && ramType !== moboRam) issues.push(
                    `Tipe RAM (${ramType}) tidak cocok dengan slot Motherboard (${moboRam})`);
            }

            if (build.ram && build.cpu) {
                const ramType = getSpec(build.ram, 'ram_type');
                const cpuRam = getSpec(build.cpu, 'ram_type_support');
                if (ramType && cpuRam && !specSupports(cpuRam, ramType)) issues.push(
                    `RAM ${ramType} tidak ada di dukungan Processor (${cpuRam})`);
            }

            if (build.casing && build.motherboard) {
                const supported = getSpec(build.casing, 'supported_motherboard_sizes');
                const formFactor = getSpec(build.motherboard, 'form_factor');
                if (supported && formFactor && !specSupports(supported, formFactor)) issues.push(
                    `Casing tidak mendukung form factor Motherboard ${formFactor}`);
            }

            if (build.casing && build.vga) {
                const maxGpu = intSpec(build.casing, 'max_gpu_length_mm');
                const gpuLength = intSpec(build.vga, 'length_mm');
                if (maxGpu > 0 && gpuLength > 0 && gpuLength > maxGpu) issues.push(
                    `Panjang GPU ${gpuLength}mm melebihi kapasitas casing ${maxGpu}mm`);
            }

            if (build.casing && build.cpu_cooler) {
                const maxCooler = intSpec(build.casing, 'max_cpu_cooler_height_mm');
                const coolerHeight = intSpec(build.cpu_cooler, 'height_mm');
                if (maxCooler > 0 && coolerHeight > 0 && coolerHeight > maxCooler) issues.push(
                    `Tinggi Cooler ${coolerHeight}mm melebihi kapasitas casing ${maxCooler}mm`);
            }

            return issues;
        }

        function checkCompatibility() {
            const statusEl = document.getElementById('compat-status');
            const warningEl = document.getElementById('compat-warning');
            const issues = getCompatibilityIssues();

            if (issues.length) {
                showCompatError(statusEl, 'Tidak kompatibel', issues[0]);
                warningEl.textContent = issues.join(' | ');
                warningEl.classList.remove('hidden');
                return;
            }

            warningEl.classList.add('hidden');
            const filled = Object.values(build).filter(Boolean).length;

            if (filled >= 2) {
                statusEl.className = 'rounded-2xl p-4 bg-emerald-50 border border-emerald-100';
                statusEl.innerHTML = `
            <p class="text-sm font-medium text-emerald-700">Semua komponen yang dipilih kompatibel</p>
            <p class="text-xs text-emerald-600 mt-0.5">${filled} dari ${TOTAL_COMPONENTS} komponen sudah dipilih</p>
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

        function resetBuild() {
            if (!confirm('Reset semua pilihan komponen?')) return;
            Object.keys(build).forEach(k => build[k] = null);
            location.reload();
        }

        function generateBuildHtml(customName = null, customNotes = null) {
            const cpuTdp = intSpec(build.cpu, 'tdp_watt');
            const gpuMinPsu = intSpec(build.vga, 'min_psu_watt');
            const totalNeed = cpuTdp + gpuMinPsu;
            const recommended = totalNeed > 0 ? getRecommendedPsuWatt() : null;
            const psuWattage = intSpec(build.psu, 'total_wattage');

            let total = 0;
            Object.values(build).forEach(p => {
                if (p) total += productLineTotal(p);
            });
            const totalJual = window._totalJual || total;

            const rows = Object.entries(LABELS).filter(([key]) => build[key]).map(([key, label]) => {
                const p = build[key];
                const specStr = p.specs ?
                    Object.entries(p.specs).slice(0, 4).map(([k, v]) => `${k.replace(/_/g, ' ')}: ${v}`).join(
                    ' - ') :
                    '';

                return `
            <tr>
                <td class="label">${label}</td>
                <td class="name">
                    ${p.brand ? `<span class="badge">${p.brand}</span> ` : ''}
                    ${p.name}
                    ${productQty(p) > 1 ? `<span class="qty">x${productQty(p)}</span>` : ''}
                    ${specStr ? `<br><span class="specs">${specStr}</span>` : ''}
                </td>
            </tr>`;
            }).join('');

            let compatWarning = getCompatibilityIssues().map(issue => `<div class="warning">${issue}</div>`).join('');
            if (psuWattage > 0 && recommended && psuWattage < recommended) {
                compatWarning +=
                    `<div class="warning">PSU ${psuWattage}W mungkin tidak cukup. Direkomendasikan minimal ${recommended}W.</div>`;
            }

            const printDate = new Date().toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });

            const documentTitle = customName ? customName : 'Estimasi Rakit PC';
            const notesSection = customNotes ? `
            <div class="section" style="margin-top: 14px;">
                <div class="section-title">Catatan</div>
                <div style="font-size: 12px; color: #334155; white-space: pre-line; line-height: 1.5;">${escapeHtml(customNotes)}</div>
            </div>` : '';

            return `<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>${escapeHtml(documentTitle)}</title>
        <style>
            @page { margin: 0; size: A4 portrait; }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1e293b; padding: 32px; background: #fff; }
            .header { border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
            .header h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
            .header p  { font-size: 11px; color: #64748b; margin-top: 3px; }
            .date { font-size: 11px; color: #64748b; text-align: right; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            thead tr { background: #0f172a; color: #fff; }
            thead th { padding: 9px 12px; text-align: left; font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; }
            tbody tr { border-bottom: 1px solid #e2e8f0; }
            tbody tr:last-child { border-bottom: none; }
            td { padding: 10px 12px; vertical-align: top; }
            td.label { font-weight: 600; color: #475569; width: 170px; white-space: nowrap; }
            td.name { color: #1e293b; line-height: 1.5; }
            .badge { display: inline-block; font-size: 10px; background: #f1f5f9; color: #64748b; padding: 1px 6px; border-radius: 4px; font-weight: 600; margin-right: 4px; }
            .qty { display: inline-block; font-size: 10px; background: #ecfdf5; color: #047857; padding: 1px 6px; border-radius: 4px; font-weight: 700; margin-left: 5px; }
            .specs { font-size: 10px; color: #94a3b8; }
            .total-row { background: #f8fafc; }
            .total-row td { padding: 12px; font-weight: 700; font-size: 14px; }
            .section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; margin-bottom: 14px; }
            .section-title { font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: #64748b; margin-bottom: 8px; }
            .watt-info { display: flex; justify-content: space-between; font-size: 12px; color: #334155; }
            .watt-info span { font-weight: 600; }
            .warning { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; color: #c2410c; font-size: 11px; }
            .footer { margin-top: 28px; border-top: 1px solid #e2e8f0; padding-top: 12px; font-size: 10px; color: #94a3b8; text-align: center; }
            @media print { body { padding: 20px; } button { display: none !important; } }
        </style>
    </head>
    <body>
        <div class="header">
            <div>
                <h1>${escapeHtml(documentTitle)}</h1>
                <p>Simulasi konfigurasi dan kompatibilitas komponen PC</p>
            </div>
            <div class="date">Dicetak: ${printDate}</div>
        </div>

        ${compatWarning}

        <table>
            <thead>
                <tr><th>Komponen</th><th>Rincian Item</th></tr>
            </thead>
            <tbody>
                ${rows}
                <tr class="total-row"><td>Total Estimasi</td><td style="font-size:16px;color:#0f172a;">Rp ${totalJual.toLocaleString('id-ID')}</td></tr>
            </tbody>
        </table>

        ${totalNeed > 0 ? `
            <div class="section">
                <div class="section-title">Estimasi Konsumsi Daya</div>
                <div class="watt-info"><span>CPU TDP</span><span>${cpuTdp} W</span></div>
                <div class="watt-info"><span>GPU Min PSU</span><span>${gpuMinPsu} W</span></div>
                <div class="watt-info" style="margin-top:6px;padding-top:6px;border-top:1px solid #e2e8f0;"><span>Total Estimasi</span><span>${totalNeed} W</span></div>
                <div class="watt-info"><span>Rekomendasi PSU (headroom 30%)</span><span style="color:#0f172a;">minimal ${recommended} W</span></div>
                ${psuWattage ? `<div class="watt-info"><span>PSU Dipilih</span><span>${psuWattage} W</span></div>` : ''}
            </div>` : ''}

        ${notesSection}

        <div class="footer">Dokumen ini merupakan estimasi. Harga dapat berubah sewaktu-waktu.</div>
        <script>window.onload = () => { window.print(); }<\/script>
    </body>
    </html>`;
        }

        function saveBuild() {
            const filled = Object.entries(build).filter(([, v]) => v !== null);
            if (filled.length === 0) {
                alert('Pilih minimal satu komponen terlebih dahulu.');
                return;
            }

            const html = generateBuildHtml();
            const w = window.open('', '_blank', 'width=800,height=700');
            w.document.write(html);
            w.document.close();
        }
        const CSRF_TOKEN = '{{ csrf_token() }}';

        // =============================================================================
        // ini untuk buka modal simpan build, yang isinya form untuk input nama build + catatan
        // =============================================================================
        function openSaveBuildModal() {
            const filled = Object.values(build).filter(Boolean);
            if (filled.length === 0) {
                alert('Pilih minimal satu komponen dulu.');
                return;
            }
            document.getElementById('modal-save-build').classList.remove('hidden');
            document.getElementById('modal-save-build').classList.add('flex');
        }

        function closeSaveBuildModal() {
            document.getElementById('modal-save-build').classList.add('hidden');
            document.getElementById('modal-save-build').classList.remove('flex');
        }

        function showSuccessMessage(message) {
            const toast = document.getElementById('success-toast');
            if (!toast) return;

            toast.textContent = message;
            toast.classList.remove('hidden');

            clearTimeout(window._successToastTimer);
            window._successToastTimer = setTimeout(() => {
                toast.classList.add('hidden');
            }, 3500);
        }

        async function confirmSaveBuild() {
            const name = document.getElementById('save-build-name').value.trim();
            if (!name) {
                alert('Nama build wajib diisi.');
                return;
            }

            const filled = Object.values(build).filter(Boolean);
            if (filled.length === 0) {
                alert('Pilih minimal satu komponen dulu.');
                return;
            }

            const payload = {
                name,
                notes: document.getElementById('save-build-notes').value.trim(),
                components: build,
                margin_pct: window._marginPct || 0,
                total_modal: window._totalModal || 0,
                harga_jual: window._totalJual || 0,
            };

            try {
                const res = await fetch('/pc-builder/builds', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify(payload),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'Gagal menyimpan');
                }

                const result = await res.json();
                const buildId = result.build?.id;

                if (buildId) {
                    // Trigger direct PDF download using server-side DomPDF
                    window.location.href = `/pc-builder/builds/${buildId}/pdf`;
                }

                closeSaveBuildModal();
                document.getElementById('save-build-name').value = '';
                document.getElementById('save-build-notes').value = '';
                showSuccessMessage('Build berhasil disimpan');

                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        // =============================================================================
        // EVENT LISTENERS
        // =============================================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Tutup modal jika klik di backdrop
            document.getElementById('modal-overlay').addEventListener('click', function(e) {
                if (e.target === this) closeComponentModal();
            });

            // Tutup modal dengan tombol Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && currentModalKey !== null) {
                    closeComponentModal();
                }
            });
        });
    </script>
@endsection
