@extends('layouts.app')

@section('title', 'Simulasi Rakit PC')

@section('content')
<div class="px-5">

    {{-- HEADER --}}
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        {{-- KIRI: COMPONENT SELECTOR --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- ============================================================
                 COMPONENT ROWS
                 Urutan baru: Motherboard → CPU → RAM → VGA → Storage → PSU
                 Motherboard adalah entry point (selalu aktif).
                 CPU dan RAM difilter berdasarkan spec dari Motherboard.
                 VGA dan Storage aktif setelah CPU dipilih.
                 PSU aktif setelah CPU + VGA dipilih.
            ============================================================ --}}

            {{-- 1. MOTHERBOARD (entry point, selalu aktif) --}}
            <div class="bg-white rounded-2xl shadow-sm p-5" id="row-motherboard">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div id="icon-motherboard"
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold flex-shrink-0">
                            1
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Motherboard</p>
                            <p id="name-motherboard" class="font-medium text-slate-400 italic text-sm">Belum dipilih</p>
                            <p id="price-motherboard" class="text-sm text-slate-400 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    {{-- Motherboard selalu aktif — tidak perlu disabled --}}
                    <button onclick="openComponentModal('Motherboard', 'motherboard')"
                        id="btn-motherboard"
                        class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800 transition">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 2. CPU (aktif setelah Motherboard dipilih, difilter by socket) --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50" id="row-cpu">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div id="icon-cpu"
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold flex-shrink-0">
                            2
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Processor (CPU)</p>
                            {{-- Placeholder berubah karena sekarang CPU menunggu Motherboard --}}
                            <p id="name-cpu" class="font-medium text-slate-400 italic text-sm">Pilih Motherboard dulu</p>
                            <p id="price-cpu" class="text-sm text-slate-400 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('Processor', 'cpu')"
                        id="btn-cpu"
                        disabled
                        class="px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 3. RAM (aktif setelah Motherboard dipilih, difilter by ram_type) --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50" id="row-ram">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div id="icon-ram"
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold flex-shrink-0">
                            3
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">RAM</p>
                            <p id="name-ram" class="font-medium text-slate-400 italic text-sm">Pilih Motherboard dulu</p>
                            <p id="price-ram" class="text-sm text-slate-400 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('RAM', 'ram')"
                        id="btn-ram"
                        disabled
                        class="px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 4. VGA (aktif setelah CPU dipilih) --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50" id="row-vga">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div id="icon-vga"
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold flex-shrink-0">
                            4
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">VGA / GPU</p>
                            <p id="name-vga" class="font-medium text-slate-400 italic text-sm">Pilih CPU dulu</p>
                            <p id="price-vga" class="text-sm text-slate-400 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('VGA', 'vga')"
                        id="btn-vga"
                        disabled
                        class="px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 5. Storage (aktif setelah CPU dipilih) --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50" id="row-storage">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div id="icon-storage"
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold flex-shrink-0">
                            5
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Storage</p>
                            <p id="name-storage" class="font-medium text-slate-400 italic text-sm">Pilih CPU dulu</p>
                            <p id="price-storage" class="text-sm text-slate-400 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('Storage', 'storage')"
                        id="btn-storage"
                        disabled
                        class="px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- 6. PSU (aktif setelah CPU + VGA dipilih) --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 opacity-50" id="row-psu">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div id="icon-psu"
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold flex-shrink-0">
                            6
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Power Supply</p>
                            <p id="name-psu" class="font-medium text-slate-400 italic text-sm">Pilih CPU & VGA dulu</p>
                            <p id="price-psu" class="text-sm text-slate-400 mt-0.5 hidden"></p>
                        </div>
                    </div>
                    <button onclick="openComponentModal('Power Supply', 'psu')"
                        id="btn-psu"
                        disabled
                        class="px-4 py-2 rounded-xl bg-slate-200 text-slate-400 text-sm cursor-not-allowed transition">
                        Pilih
                    </button>
                </div>
            </div>

            {{-- STATUS KOMPATIBILITAS --}}
            <div id="compat-status" class="hidden rounded-2xl p-5">
                <p id="compat-text" class="text-sm font-medium"></p>
                <p id="compat-sub" class="text-xs mt-1"></p>
            </div>

        </div>

        {{-- KANAN: BUILD SUMMARY --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col h-fit sticky top-5">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Ringkasan Build</h2>

            {{-- Urutan summary disesuaikan dengan urutan baru: Motherboard dulu --}}
            <div class="space-y-3 flex-1 text-sm" id="summary-list">
                @foreach(['motherboard' => 'Motherboard', 'cpu' => 'Processor', 'ram' => 'RAM', 'vga' => 'VGA', 'storage' => 'Storage', 'psu' => 'PSU'] as $key => $label)
                    <div class="flex justify-between" id="summary-{{ $key }}">
                        <span class="text-slate-500">{{ $label }}</span>
                        <span id="summary-price-{{ $key }}" class="text-slate-300">—</span>
                    </div>
                @endforeach
            </div>

            {{-- ESTIMASI DAYA --}}
            <div class="mt-6 bg-slate-50 rounded-xl p-4 text-sm">
                <div class="flex justify-between mb-1">
                    <span class="text-slate-500">Estimasi Daya</span>
                    <span class="font-medium" id="summary-watt">— W</span>
                </div>
                <p class="text-xs text-slate-400" id="summary-psu-rec">Pilih CPU & VGA untuk estimasi daya</p>
            </div>

            {{-- TOTAL --}}
            <div class="mt-6 pt-4 border-t">
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total Estimasi</span>
                    <span id="summary-total">Rp 0</span>
                </div>
                <button
                    class="w-full mt-5 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800 transition"
                    onclick="saveBuild()">
                    Simpan Build
                </button>
            </div>
        </div>

    </div>
</div>

{{-- ============================================================
     MODAL PILIH KOMPONEN
     id="modal-overlay" dipakai khusus untuk modal PC Builder ini,
     berbeda dengan modal logout milik layout yang pakai id dinamis.
============================================================ --}}
<div id="modal-overlay"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between p-6 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-semibold text-slate-800" id="modal-title">Pilih Komponen</h3>
                <p class="text-xs text-slate-400 mt-0.5" id="modal-compat-info"></p>
            </div>
            {{-- Pakai closeComponentModal agar tidak konflik dengan closeModal milik layout --}}
            <button onclick="closeComponentModal()" class="text-slate-400 hover:text-slate-700 text-xl">×</button>
        </div>

        {{-- Loading --}}
        <div id="modal-loading" class="flex items-center justify-center py-16 hidden">
            <div class="text-center">
                <div class="w-8 h-8 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-sm text-slate-500">Memuat produk yang kompatibel...</p>
            </div>
        </div>

        {{-- Empty --}}
        <div id="modal-empty" class="flex items-center justify-center py-16 hidden">
            <div class="text-center">
                <p class="text-slate-400 text-sm">Tidak ada produk yang tersedia / kompatibel.</p>
                <p class="text-slate-300 text-xs mt-1">Pastikan stok tersedia dan spec sudah diisi.</p>
            </div>
        </div>

        {{-- Product List --}}
        <div id="modal-list" class="overflow-y-auto flex-1 p-4 space-y-3"></div>
    </div>
</div>

<script>
    // ============================================================
    // STATE — menyimpan semua komponen yang sudah dipilih
    // ============================================================
    const build = {
        motherboard: null, // entry point baru — dipilih pertama kali
        cpu:         null, // difilter by socket dari Motherboard
        ram:         null, // difilter by ram_type dari Motherboard
        vga:         null,
        storage:     null,
        psu:         null,
    };

    let currentModalKey = null; // key yang sedang dibuka di modal

    // ============================================================
    // OPEN COMPONENT MODAL
    // Nama fungsi sengaja dibuat openComponentModal (bukan openModal)
    // karena layouts/app.blade.php sudah punya fungsi openModal(id)
    // untuk modal logout — kalau namanya sama, fungsi layout akan
    // menimpa fungsi ini dan menyebabkan error "modal is null".
    //
    // type = nama kategori di DB (misal 'Motherboard', 'Processor')
    // key  = key di object build (misal 'motherboard', 'cpu')
    // ============================================================
    async function openComponentModal(type, key) {
        currentModalKey = key;

        // Tampilkan overlay
        const overlay = document.getElementById('modal-overlay');
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');

        // Set judul modal
        document.getElementById('modal-title').textContent = 'Pilih ' + type;
        document.getElementById('modal-list').innerHTML = '';
        document.getElementById('modal-empty').classList.add('hidden');

        // ============================================================
        // INFO KOMPATIBILITAS DI MODAL
        // Tampilkan hint filter apa yang sedang aktif
        // ============================================================
        let compatInfo = '';
        if (key === 'cpu' && build.motherboard) {
            // CPU difilter by socket dari Motherboard yang sudah dipilih
            compatInfo = 'Filter: Socket ' + (build.motherboard.specs?.socket || '?');
        } else if (key === 'ram' && build.motherboard) {
            // RAM difilter by ram_type dari Motherboard yang sudah dipilih
            compatInfo = 'Filter: ' + (build.motherboard.specs?.ram_type || '?');
        }
        document.getElementById('modal-compat-info').textContent = compatInfo;

        // Tampilkan loading spinner
        document.getElementById('modal-loading').classList.remove('hidden');

        // ============================================================
        // BUILD QUERY PARAMS
        // Kirim filter yang relevan ke controller:
        // - Motherboard: tidak perlu filter (bebas pilih)
        // - CPU: kirim socket dari Motherboard
        // - RAM: kirim ram_type dari Motherboard
        // - Lainnya: tidak ada filter khusus
        // ============================================================
        const params = new URLSearchParams({ type });

        if (key === 'cpu' && build.motherboard?.specs?.socket) {
            // Kirim socket Motherboard → controller akan filter CPU yang socketnya cocok
            params.set('socket', build.motherboard.specs.socket);
        }

        if (key === 'ram' && build.motherboard?.specs?.ram_type) {
            // Kirim ram_type Motherboard → controller akan filter RAM yang typenya cocok
            params.set('ram_type', build.motherboard.specs.ram_type);
        }

        // Fetch produk dari server
        try {
            const res = await fetch(`/pc-builder/compatible?${params}`);
            const products = await res.json();

            document.getElementById('modal-loading').classList.add('hidden');

            if (products.length === 0) {
                document.getElementById('modal-empty').classList.remove('hidden');
                return;
            }

            // Render daftar produk di modal
            const list = document.getElementById('modal-list');
            products.forEach(p => {
                const isSelected = build[key]?.id === p.id;
                const div = document.createElement('div');
                div.className = `p-4 rounded-xl border cursor-pointer transition hover:border-slate-400 ${isSelected ? 'border-slate-900 bg-slate-50' : 'border-slate-200'}`;
                div.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-slate-800 text-sm">${p.name}</p>
                            <p class="text-xs text-slate-400 mt-0.5">${formatSpecs(p.specs)}</p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="font-semibold text-slate-800 text-sm">${p.price_fmt}</p>
                            ${isSelected ? '<p class="text-xs text-emerald-600 mt-0.5">✓ Dipilih</p>' : ''}
                        </div>
                    </div>
                `;
                div.addEventListener('click', () => selectComponent(key, p));
                list.appendChild(div);
            });

        } catch (e) {
            document.getElementById('modal-loading').classList.add('hidden');
            document.getElementById('modal-empty').classList.remove('hidden');
        }
    }

    // Format specs jadi string singkat untuk ditampilkan di modal
    function formatSpecs(specs) {
        if (!specs) return '';
        return Object.entries(specs)
            .slice(0, 4) // Tampilkan max 4 spec
            .map(([k, v]) => `${k}: ${v}`)
            .join(' · ');
    }

    // ============================================================
    // CLOSE COMPONENT MODAL
    // Sama seperti openComponentModal, nama ini sengaja berbeda
    // dari closeModal milik layout agar tidak saling menimpa.
    // ============================================================
    function closeComponentModal() {
        const overlay = document.getElementById('modal-overlay');
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        currentModalKey = null;
    }

    // ============================================================
    // SELECT COMPONENT — dipanggil saat user klik salah satu produk di modal
    // ============================================================
    function selectComponent(key, product) {
        build[key] = product;
        closeComponentModal(); // ← pakai closeComponentModal, bukan closeModal milik layout

        // Update tampilan baris komponen yang baru dipilih
        updateRow(key, product);

        // Unlock komponen berikutnya sesuai dependency chain
        unlockNext(key);

        // Perbarui ringkasan build di sidebar kanan
        updateSummary();

        // Cek ulang status kompatibilitas
        updateCompatStatus();
    }

    // Update tampilan baris komponen setelah dipilih
    function updateRow(key, product) {
        const nameEl  = document.getElementById(`name-${key}`);
        const priceEl = document.getElementById(`price-${key}`);
        const iconEl  = document.getElementById(`icon-${key}`);
        const rowEl   = document.getElementById(`row-${key}`);
        const btnEl   = document.getElementById(`btn-${key}`);

        nameEl.textContent  = product.name;
        nameEl.classList.remove('text-slate-400', 'italic');
        nameEl.classList.add('text-slate-800');

        priceEl.textContent = product.price_fmt;
        priceEl.classList.remove('hidden');
        priceEl.classList.add('text-emerald-600', 'font-medium');

        iconEl.textContent = '✓';
        iconEl.classList.remove('bg-slate-100', 'text-slate-400');
        iconEl.classList.add('bg-emerald-100', 'text-emerald-600');

        rowEl.classList.remove('opacity-50');

        btnEl.textContent = 'Ganti';
    }

    // ============================================================
    // UNLOCK NEXT — dependency chain baru (Motherboard-first)
    //
    // Alur dependency:
    //   Motherboard dipilih → unlock CPU (filter socket) + RAM (filter ram_type) + Storage
    //   CPU dipilih         → unlock VGA
    //   CPU + VGA dipilih   → unlock PSU
    // ============================================================
    function unlockNext(key) {
        if (key === 'motherboard') {
            // Motherboard dipilih → CPU dan RAM bisa dipilih (dengan filter dari mobo)
            // Storage juga bebas dipilih setelah mobo ada
            enableRow('cpu', 'Filter: Socket ' + (build.motherboard?.specs?.socket || '?'));
            enableRow('ram', 'Filter: ' + (build.motherboard?.specs?.ram_type || 'DDR?'));
            enableRow('storage', 'Bebas pilih Storage');
        }

        if (key === 'cpu') {
            // CPU dipilih → VGA bisa dipilih
            enableRow('vga', 'Bebas pilih VGA');
        }

        if (key === 'cpu' || key === 'vga') {
            // PSU baru aktif kalau CPU DAN VGA keduanya sudah dipilih
            // karena PSU dihitung dari total TDP CPU + VGA
            if (build.cpu && build.vga) {
                enableRow('psu', 'Berdasarkan estimasi daya');
            }
        }
    }

    // Aktifkan sebuah baris komponen (hapus disabled & opacity)
    function enableRow(key, placeholder) {
        const row = document.getElementById(`row-${key}`);
        const btn = document.getElementById(`btn-${key}`);
        const name = document.getElementById(`name-${key}`);

        row.classList.remove('opacity-50');
        btn.disabled = false;
        btn.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
        btn.classList.add('bg-slate-900', 'text-white', 'hover:bg-slate-800');

        // Update placeholder hanya kalau komponen ini belum dipilih
        if (!build[key]) {
            name.textContent = placeholder;
            name.classList.add('text-slate-400', 'italic');
        }
    }

    // ============================================================
    // SUMMARY UPDATE — perbarui total harga dan estimasi daya
    // ============================================================
    function updateSummary() {
        let total = 0;
        let totalTdp = 0;

        Object.entries(build).forEach(([key, product]) => {
            const el = document.getElementById(`summary-price-${key}`);
            if (product) {
                el.textContent = product.price_fmt;
                el.classList.remove('text-slate-300');
                el.classList.add('text-slate-800', 'font-medium');
                total += product.price;

                // Akumulasi TDP untuk estimasi kebutuhan daya
                if (product.specs?.tdp) {
                    totalTdp += parseInt(product.specs.tdp) || 0;
                }
            }
        });

        // Tampilkan total harga
        document.getElementById('summary-total').textContent =
            'Rp ' + total.toLocaleString('id-ID');

        // Tampilkan estimasi daya kalau sudah ada TDP
        if (totalTdp > 0) {
            const recommended = Math.ceil((totalTdp * 1.3) / 50) * 50; // +30% headroom, bulatkan ke 50W
            document.getElementById('summary-watt').textContent = totalTdp + ' W';
            document.getElementById('summary-psu-rec').textContent =
                `PSU minimal ${recommended}W direkomendasikan`;
        }
    }

    // ============================================================
    // KOMPATIBILITAS CHECK
    // Cek apakah komponen yang dipilih saling cocok.
    // Meskipun sudah difilter di server, pengecekan ini tetap berguna
    // kalau user mengganti salah satu komponen setelah yang lain dipilih.
    // ============================================================
    function updateCompatStatus() {
        const statusEl = document.getElementById('compat-status');
        const textEl   = document.getElementById('compat-text');
        const subEl    = document.getElementById('compat-sub');

        // Cek socket: CPU harus cocok dengan Motherboard
        if (build.cpu && build.motherboard) {
            const cpuSocket  = build.cpu.specs?.socket;
            const moboSocket = build.motherboard.specs?.socket;

            if (cpuSocket && moboSocket && cpuSocket !== moboSocket) {
                statusEl.className = 'rounded-2xl p-5 bg-red-50 border border-red-100';
                textEl.className   = 'text-sm font-medium text-red-700';
                textEl.textContent = '⚠ Tidak Kompatibel: Socket tidak cocok';
                subEl.className    = 'text-xs text-red-600 mt-1';
                subEl.textContent  = `CPU: ${cpuSocket} — Motherboard: ${moboSocket}`;
                statusEl.classList.remove('hidden');
                return;
            }
        }

        // Cek ram_type: RAM harus cocok dengan Motherboard
        if (build.ram && build.motherboard) {
            const moboRam = build.motherboard.specs?.ram_type;
            const ramType = build.ram.specs?.ram_type;

            if (moboRam && ramType && moboRam !== ramType) {
                statusEl.className = 'rounded-2xl p-5 bg-red-50 border border-red-100';
                textEl.className   = 'text-sm font-medium text-red-700';
                textEl.textContent = '⚠ Tidak Kompatibel: Tipe RAM tidak cocok';
                subEl.className    = 'text-xs text-red-600 mt-1';
                subEl.textContent  = `Motherboard support ${moboRam} — RAM yang dipilih ${ramType}`;
                statusEl.classList.remove('hidden');
                return;
            }
        }

        // Semua komponen yang dipilih kompatibel
        const filled = Object.values(build).filter(Boolean).length;
        if (filled >= 2) {
            statusEl.className = 'rounded-2xl p-5 bg-emerald-50 border border-emerald-100';
            textEl.className   = 'text-sm font-medium text-emerald-700';
            textEl.textContent = '✓ Semua komponen yang dipilih kompatibel';
            subEl.className    = 'text-xs text-emerald-600 mt-1';
            subEl.textContent  = `${filled} dari 6 komponen sudah dipilih`;
            statusEl.classList.remove('hidden');
        } else {
            statusEl.classList.add('hidden');
        }
    }

    // ============================================================
    // RESET BUILD — kembali ke kondisi awal
    // ============================================================
    function resetBuild() {
        if (!confirm('Reset semua pilihan komponen?')) return;
        Object.keys(build).forEach(k => build[k] = null);
        location.reload();
    }

    // ============================================================
    // SAVE BUILD (opsional — bisa diarahkan ke halaman simpan)
    // ============================================================
    function saveBuild() {
        const filled = Object.values(build).filter(Boolean);
        if (filled.length === 0) {
            alert('Pilih minimal satu komponen terlebih dahulu.');
            return;
        }
        // TODO: kirim ke backend kalau mau simpan
        alert('Fitur simpan build segera hadir!');
    }

    // Tutup modal kalau klik di luar area modal (klik backdrop)
    // Pakai closeComponentModal, bukan closeModal milik layout
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('modal-overlay').addEventListener('click', function (e) {
            if (e.target === this) closeComponentModal();
        });
    });
</script>
@endsection
