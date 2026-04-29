<?php

/**
 * =============================================================================
 * config/product_specs.php
 * =============================================================================
 *
 * File ini adalah SATU-SATUNYA sumber kebenaran untuk:
 *   1. Struktur field spesifikasi per kategori produk
 *   2. Key mana yang wajib untuk simulasi rakit PC
 *   3. Alias key untuk toleransi inkonsistensi nama
 *
 * ALUR DATA:
 *   config ini
 *     → ProductController         → form input produk (field & dropdown)
 *     → SpecValuePresetController → halaman manajemen nilai spec
 *     → PcBuilderController       → filter kompatibilitas simulasi
 *                                   (via spec_value_presets + product_spec_value)
 *
 * ARSITEKTUR SPEC YANG DIPAKAI:
 *   spec_value_presets          → satu-satunya tempat nilai spec tersimpan
 *   product_spec_value (pivot)  → menghubungkan produk ke preset
 *   product_specifications      → SUDAH DIHAPUS, tidak dipakai lagi
 *
 * ATURAN PENTING:
 *   - Setiap 'key' di sini HARUS sama persis dengan spec_key di tabel
 *     spec_value_presets (bukan lagi product_specifications)
 *   - Kalau tambah field baru → tambah preset awal via halaman Manajemen
 *     Nilai Spesifikasi, atau lewat SpecValuePreset::firstOrCreate() di seeder
 *   - Kalau tambah kategori baru → cukup tambah di sini, PcBuilderController
 *     sudah membaca otomatis dari 'labels'
 * =============================================================================
 */

return [

    // =========================================================================
    // STRICT KEYS
    //
    // Key yang WAJIB diisi saat input produk.
    // Tanpa key ini, simulasi tidak bisa memfilter dengan benar.
    //
    // Ditampilkan sebagai "Required" di halaman manajemen spec & form produk.
    // =========================================================================
    'strict_keys' => [
        'socket_type',   // Mobo ↔ CPU ↔ CPU Cooler — kunci utama kompatibilitas
        'ram_type',      // RAM ↔ Mobo (via ram_type_slot)
        'form_factor',   // Mobo ↔ Casing ↔ PSU ↔ Storage
    ],

    // =========================================================================
    // COMPATIBILITY ALIASES
    //
    // Beberapa produk lama mungkin tersimpan dengan key yang berbeda.
    // Alias ini dipakai ProductController saat memuat dropdown
    // supaya nilai dari key lama tetap muncul.
    //
    // Format: 'canonical_key' => ['alias1', 'alias2']
    // =========================================================================
    'compatibility_aliases' => [
        'socket_type'   => ['socket'],          // 'socket' → canonical 'socket_type'
        'ram_type_slot' => ['ram_type'],         // mobo: 'ram_type' → canonical 'ram_type_slot'
        'ram_type'      => ['ram_type'],         // RAM: tetap 'ram_type'
    ],

    // =========================================================================
    // CATEGORIES
    //
    // Daftar semua kategori produk beserta field spesifikasinya.
    //
    // Setiap kategori punya:
    //   'labels'  → nama kategori yang ada di tabel categories (bisa lebih dari satu
    //               untuk toleransi inkonsistensi, mis. 'VGA' dan 'VGA Card')
    //   'fields'  → daftar field spec yang muncul di form input produk
    //
    // Setiap field punya:
    //   'key'         → canonical key, HARUS sama dengan spec_key di DB
    //   'label'       → label yang tampil di form
    //   'placeholder' → contoh nilai
    //   'hint'        → penjelasan singkat, tampil di bawah field & halaman manajemen spec
    //   'lookup_keys' → key alternatif untuk mencari nilai di dropdown
    //                   (berguna kalau data lama pakai key berbeda)
    // =========================================================================
    'categories' => [

        // =====================================================================
        // PROCESSOR
        //
        // Kompatibilitas di simulasi:
        //   socket_type  → dicocokkan dengan socket_type Motherboard
        //   tdp_watt     → dipakai untuk estimasi kebutuhan daya PSU
        //                  (cpu_tdp + gpu_min_psu) * 1.3 → min PSU watt
        // =====================================================================
        'processor' => [
            'labels' => ['Processor'],
            'fields' => [
                [
                    'key'         => 'socket_type',
                    'label'       => 'Socket type',
                    'placeholder' => 'AM5 / LGA1700',
                    // ↓ KEY WAJIB — tanpa ini CPU tidak muncul di filter simulasi
                    'hint'        => 'Wajib untuk pencocokan processor dan motherboard.',
                    'lookup_keys' => ['socket', 'Socket'],
                ],
                [
                    'key'         => 'chipset_support',
                    'label'       => 'Chipset support',
                    'placeholder' => 'B650 / Z790',
                    'hint'        => 'Opsional. Membantu mengelompokkan processor dengan platform chipset yang relevan.',
                    'lookup_keys' => ['chipset_support', 'chipset', 'Chipset'],
                ],
                [
                    'key'         => 'ram_type_support',
                    'label'       => 'RAM type support',
                    'placeholder' => 'DDR4 / DDR5',
                    'hint'        => 'Opsional. Membantu rekomendasi RAM yang kompatibel dengan processor.',
                    'lookup_keys' => ['ram_type_support', 'RAM Type Support', 'ram_type', 'RAM Type'],
                ],
                [
                    'key'         => 'tdp_watt',
                    'label'       => 'TDP watt',
                    'placeholder' => '65',
                    // ↓ Dipakai untuk kalkulasi PSU di JS: (tdp_watt + min_psu_watt) * 1.3
                    'hint'        => 'Opsional. Dipakai simulasi untuk menghitung estimasi kebutuhan daya PSU.',
                    'lookup_keys' => ['tdp_watt', 'tdp', 'TDP'],
                ],
            ],
        ],

        // =====================================================================
        // MOTHERBOARD
        //
        // Kompatibilitas di simulasi:
        //   socket_type   → jadi PATOKAN filter CPU dan CPU Cooler
        //   ram_type_slot → jadi PATOKAN filter RAM (dikirim sebagai param 'ram_type')
        //   form_factor   → untuk validasi dengan Casing (belum diimplementasi di blade,
        //                   tapi data sudah tersimpan — siap dipakai)
        // =====================================================================
        'motherboard' => [
            'labels' => ['Motherboard'],
            'fields' => [
                [
                    'key'         => 'socket_type',
                    'label'       => 'Socket type',
                    'placeholder' => 'AM5 / LGA1700',
                    // ↓ KEY WAJIB — nilai ini dikirim JS ke server untuk filter CPU
                    'hint'        => 'Wajib. Nilai ini dipakai simulasi untuk memfilter CPU dan CPU Cooler yang kompatibel.',
                    'lookup_keys' => ['socket', 'Socket'],
                ],
                [
                    'key'         => 'ram_type_slot',
                    'label'       => 'RAM type slot',
                    'placeholder' => 'DDR4 / DDR5',
                    // ↓ Nilai ini dikirim JS ke server sebagai param 'ram_type' untuk filter RAM
                    'hint'        => 'Opsional tapi penting. Dipakai simulasi untuk memfilter RAM yang sesuai slot.',
                    'lookup_keys' => ['ram_type_slot', 'ram_type', 'RAM Type'],
                ],
                [
                    'key'         => 'form_factor',
                    'label'       => 'Form factor',
                    'placeholder' => 'ATX / M-ATX / ITX',
                    // ↓ KEY WAJIB — untuk validasi kecocokan fisik dengan Casing
                    'hint'        => 'Wajib. Dipakai untuk memvalidasi apakah motherboard muat di casing yang dipilih.',
                    'lookup_keys' => ['form_factor', 'Form Factor'],
                ],
                [
                    'key'         => 'chipset',
                    'label'       => 'Chipset',
                    'placeholder' => 'B650 / Z790',
                    'hint'        => 'Opsional. Membantu identifikasi platform dan fitur motherboard.',
                    'lookup_keys' => ['chipset', 'Chipset'],
                ],
            ],
        ],

        // =====================================================================
        // RAM
        //
        // Kompatibilitas di simulasi:
        //   ram_type → dicocokkan dengan ram_type_slot Motherboard
        //              Server filter: spec_key='ram_type', spec_value='{mobo.ram_type_slot}'
        // =====================================================================
        'ram' => [
            'labels' => ['RAM'],
            'fields' => [
                [
                    'key'         => 'ram_type',
                    'label'       => 'RAM type',
                    'placeholder' => 'DDR4 / DDR5',
                    // ↓ KEY WAJIB — dicocokkan ke ram_type_slot motherboard
                    'hint'        => 'Wajib. Harus cocok dengan RAM type slot motherboard yang dipilih.',
                    'lookup_keys' => ['ram_type', 'RAM Type', 'Type'],
                ],
                [
                    'key'         => 'speed_mhz',
                    'label'       => 'Speed MHz',
                    'placeholder' => '3200',
                    'hint'        => 'Opsional. Ditampilkan sebagai info di modal pemilihan RAM.',
                    'lookup_keys' => ['speed_mhz', 'speed', 'Speed'],
                ],
                [
                    'key'         => 'capacity_gb',
                    'label'       => 'Capacity GB',
                    'placeholder' => '16',
                    'hint'        => 'Opsional. Ditampilkan sebagai info kapasitas di modal.',
                    'lookup_keys' => ['capacity_gb', 'capacity', 'Capacity'],
                ],
            ],
        ],

        // =====================================================================
        // GPU / VGA
        //
        // Kompatibilitas di simulasi:
        //   min_psu_watt → dipakai bersama cpu.tdp_watt untuk filter PSU minimum
        //                  Rumus: Math.ceil((tdp_watt + min_psu_watt) * 1.3 / 50) * 50
        //   length_mm    → untuk validasi kecocokan dengan Casing (max_gpu_length_mm)
        // =====================================================================
        'gpu' => [
            // 'labels' mencakup semua nama kategori yang mungkin ada di tabel categories
            'labels' => ['GPU', 'VGA Card', 'VGA'],
            'fields' => [
                [
                    'key'         => 'pcie_version',
                    'label'       => 'PCIe version',
                    'placeholder' => 'PCIe 4.0',
                    'hint'        => 'Opsional. Ditampilkan sebagai info spesifikasi GPU.',
                    'lookup_keys' => ['pcie_version', 'pcie', 'PCIe'],
                ],
                [
                    'key'         => 'min_psu_watt',
                    'label'       => 'Minimum PSU watt',
                    'placeholder' => '650',
                    // ↓ Dipakai JS bersama tdp_watt CPU untuk filter PSU
                    'hint'        => 'Opsional tapi penting. Dipakai simulasi untuk menghitung kebutuhan PSU minimum.',
                    'lookup_keys' => ['min_psu_watt', 'recommended_psu', 'Recommended PSU'],
                ],
                [
                    'key'         => 'length_mm',
                    'label'       => 'Length mm',
                    'placeholder' => '300',
                    // ↓ Untuk validasi max_gpu_length_mm casing (belum di-implement filter,
                    //   tapi data sudah tersimpan — tinggal tambah cek di checkCompatibility JS)
                    'hint'        => 'Opsional. Untuk memvalidasi apakah GPU muat di casing yang dipilih.',
                    'lookup_keys' => ['length_mm', 'length', 'Length'],
                ],
            ],
        ],

        // =====================================================================
        // POWER SUPPLY (PSU)
        //
        // Kompatibilitas di simulasi:
        //   total_wattage → difilter server: CAST(spec_value AS UNSIGNED) >= min_wattage
        //                   min_wattage dihitung JS dari: (cpu_tdp + gpu_min_psu) * 1.3
        // =====================================================================
        'power_supply' => [
            'labels' => ['Power Supply', 'Power_Supply'],
            'fields' => [
                [
                    'key'         => 'total_wattage',
                    'label'       => 'Total wattage',
                    'placeholder' => '750',
                    // ↓ Difilter server: hanya PSU dengan total_wattage >= min_wattage yang tampil
                    'hint'        => 'Opsional tapi penting. Dipakai simulasi untuk memfilter PSU yang dayanya cukup.',
                    'lookup_keys' => ['total_wattage', 'wattage', 'Wattage'],
                ],
                [
                    'key'         => 'efficiency_rating',
                    'label'       => 'Efficiency rating',
                    'placeholder' => '80+ GOLD',
                    'hint'        => 'Opsional. Membantu menilai kualitas dan efisiensi PSU.',
                    'lookup_keys' => ['efficiency_rating', 'efficiency', 'Efficiency'],
                ],
                [
                    'key'         => 'form_factor',
                    'label'       => 'Form factor',
                    'placeholder' => 'ATX / SFX',
                    // ↓ KEY WAJIB — untuk kecocokan fisik PSU dengan casing
                    'hint'        => 'Wajib. Memastikan PSU secara fisik muat di casing yang dipilih.',
                    'lookup_keys' => ['form_factor', 'Form Factor'],
                ],
            ],
        ],

        // =====================================================================
        // CASING
        //
        // Kompatibilitas di simulasi (saat ini sebagai info + validasi manual JS):
        //   supported_motherboard_sizes → dicocokkan dengan form_factor Mobo
        //   max_gpu_length_mm           → dicocokkan dengan length_mm GPU
        //   max_cpu_cooler_height_mm    → dicocokkan dengan height_mm CPU Cooler
        //
        // CATATAN: Filter casing belum ada di PcBuilderController karena
        // pencocokan string seperti "ATX, M-ATX, ITX" contains "ATX" butuh
        // logic khusus. Tapi data sudah tersimpan — siap diimplementasi.
        // =====================================================================
        'casing' => [
            'labels' => ['Casing'],
            'fields' => [
                [
                    'key'         => 'supported_motherboard_sizes',
                    'label'       => 'Supported motherboard sizes',
                    'placeholder' => 'ATX, M-ATX, ITX',
                    // ↓ Nilai bisa berupa kombinasi: 'ATX, M-ATX, ITX'
                    //   Validasi JS: casing.supported_motherboard_sizes.includes(mobo.form_factor)
                    'hint'        => 'Opsional tapi krusial. Daftar form factor mobo yang didukung casing ini.',
                    'lookup_keys' => ['supported_motherboard_sizes', 'form_factor'],
                ],
                [
                    'key'         => 'max_gpu_length_mm',
                    'label'       => 'Max GPU length mm',
                    'placeholder' => '340',
                    // ↓ Validasi JS: gpu.length_mm <= casing.max_gpu_length_mm
                    'hint'        => 'Opsional. Panjang GPU maksimal yang muat di casing ini (dalam mm).',
                    'lookup_keys' => ['max_gpu_length_mm', 'max_gpu_length', 'Length'],
                ],
                [
                    'key'         => 'max_cpu_cooler_height_mm',
                    'label'       => 'Max CPU cooler height mm',
                    'placeholder' => '165',
                    // ↓ Validasi JS: cpu_cooler.height_mm <= casing.max_cpu_cooler_height_mm
                    'hint'        => 'Opsional. Tinggi CPU cooler maksimal yang muat di casing (dalam mm).',
                    'lookup_keys' => ['max_cpu_cooler_height_mm', 'max_cpu_cooler_height'],
                ],
            ],
        ],

        // =====================================================================
        // STORAGE
        //
        // Kompatibilitas di simulasi:
        //   Storage bebas dipilih setelah Motherboard dipilih (tidak ada filter ketat).
        //   form_factor & interface_type hanya ditampilkan sebagai info.
        //
        //   Kalau ingin validasi lebih ketat di masa depan:
        //   → Cek m2_slots / sata_ports di Mobo vs form_factor Storage
        // =====================================================================
        'storage' => [
            'labels' => ['Storage', 'Storage (SSD/HDD)'],
            'fields' => [
                [
                    'key'         => 'form_factor',
                    'label'       => 'Form factor',
                    'placeholder' => 'M.2 / 2.5 / 3.5',
                    // ↓ KEY WAJIB — M.2 butuh slot M.2, 2.5/3.5 butuh bay SATA
                    'hint'        => 'Wajib. Menentukan slot atau bay yang dibutuhkan di motherboard/casing.',
                    'lookup_keys' => ['form_factor', 'Form Factor'],
                ],
                [
                    'key'         => 'interface_type',
                    'label'       => 'Interface type',
                    'placeholder' => 'NVME / SATA',
                    'hint'        => 'Opsional. Membantu memilih storage yang kompatibel dengan slot motherboard.',
                    'lookup_keys' => ['interface_type', 'interface', 'Interface'],
                ],
                [
                    'key'         => 'capacity_gb',
                    'label'       => 'Capacity GB',
                    'placeholder' => '1000',
                    'hint'        => 'Opsional. Ditampilkan sebagai info kapasitas.',
                    'lookup_keys' => ['capacity_gb', 'capacity', 'Capacity'],
                ],
            ],
        ],

        // =====================================================================
        // CPU COOLER
        //
        // Kompatibilitas di simulasi:
        //   socket_type → dicocokkan dengan socket_type Motherboard
        //                 (sama seperti Processor — filter by socket)
        //
        // CATATAN: CPU Cooler saat ini belum ada di blade simulasi (index.blade.php).
        // Kalau ingin tambah:
        //   1. Tambah row CPU Cooler di blade (setelah CPU, sebelum/sesudah VGA)
        //   2. Aktifkan setelah Mobo dipilih (unlockNext di JS)
        //   3. Kirim socket_type ke server (sama seperti CPU)
        // =====================================================================
        'cpu_cooler' => [
            'labels' => ['CPU Cooler'],
            'fields' => [
                [
                    'key'         => 'socket_type',
                    'label'       => 'Socket type',
                    'placeholder' => 'AM5 / LGA1700',
                    // ↓ KEY WAJIB — CPU Cooler harus cocok socket dengan Motherboard
                    'hint'        => 'Wajib. CPU Cooler harus support socket yang sama dengan motherboard.',
                    'lookup_keys' => ['socket_type', 'socket', 'Socket'],
                ],
                [
                    'key'         => 'cooler_type',
                    'label'       => 'Cooler type',
                    'placeholder' => 'Air / AIO 240mm / AIO 360mm',
                    'hint'        => 'Opsional. Jenis pendingin: Air cooler atau AIO liquid cooler.',
                    'lookup_keys' => ['cooler_type', 'type', 'Type'],
                ],
                [
                    'key'         => 'height_mm',
                    'label'       => 'Height mm',
                    'placeholder' => '155',
                    // ↓ Untuk validasi max_cpu_cooler_height_mm casing
                    'hint'        => 'Opsional. Tinggi cooler dalam mm — dipakai untuk cek kecocokan dengan casing.',
                    'lookup_keys' => ['height_mm', 'height', 'Height'],
                ],
            ],
        ],

    ], // end categories

];
