<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. USERS
        // ============================================================
        User::create([
            'name'     => 'Staff',
            'email'    => 'staff@gmail.com',
            'password' => Hash::make('123'),
            'role'     => 'staff'
        ]);

        User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@gmail.com',
            'password' => Hash::make('123'),
            'role'     => 'owner'
        ]);

        // ============================================================
        // 2. CATEGORIES
        // ⚠️ PENTING: Nama kategori ini harus sama persis dengan
        //    specKeyMap di JavaScript PC Builder (create/edit blade).
        //    Jangan ubah nama di bawah kecuali kamu ubah juga di JS.
        // ============================================================
        $catProcessor   = Category::create(['name' => 'Processor']);
        $catMotherboard = Category::create(['name' => 'Motherboard']);
        $catRam         = Category::create(['name' => 'RAM']);
        $catVga         = Category::create(['name' => 'VGA']);           // Sebelumnya 'VGA Card' — disesuaikan dengan specKeyMap
        $catStorage     = Category::create(['name' => 'Storage']);       // Sebelumnya 'Storage (SSD/HDD)' — disesuaikan
        $catPsu         = Category::create(['name' => 'Power Supply']);
        $catCasing      = Category::create(['name' => 'Casing']);
        $catCooler      = Category::create(['name' => 'CPU Cooler']);    // Sebelumnya 'Cooling System' — disesuaikan

        // Peripheral & Display
        Category::create(['name' => 'Monitor']);
        Category::create(['name' => 'Keyboard']);
        Category::create(['name' => 'Mouse']);
        Category::create(['name' => 'Audio & Speaker']);
        Category::create(['name' => 'Webcam']);

        // Networking & Accessories
        Category::create(['name' => 'Networking (Wifi/LAN)']);
        Category::create(['name' => 'Cables & Adapters']);
        Category::create(['name' => 'Software & OS']);

        // Maintenance & Tools
        Category::create(['name' => 'Thermal Paste']);
        Category::create(['name' => 'Tools & Cleaning Kit']);

        // Furniture & Others
        Category::create(['name' => 'Gaming Chair & Desk']);
        Category::create(['name' => 'Lainnya']);

        // ============================================================
        // 3. SUPPLIERS
        // ============================================================
        $supplier1 = Supplier::create([
            'nama_supplier' => 'PT Sumber Teknologi',
            'alamat'        => 'Jakarta'
        ]);

        $supplier2 = Supplier::create([
            'nama_supplier' => 'CV Mega Komputer',
            'alamat'        => 'Surabaya'
        ]);

        // ============================================================
        // 4. CUSTOMERS
        // ============================================================
        Customer::create([
            'name'    => 'Budi Santoso',
            'email'   => 'budi@email.com',
            'phone'   => '081234567890',
            'address' => 'Jakarta Selatan'
        ]);

        Customer::create([
            'name'    => 'Siti Rahayu',
            'email'   => 'siti@email.com',
            'phone'   => '081234567891',
            'address' => 'Bandung'
        ]);

        // ============================================================
        // 5. PRODUCTS — PC Builder Components
        //
        // Skema kompatibilitas yang dipakai:
        //   CPU (socket) → Motherboard (socket yang sama)
        //   Motherboard (ram_type) → RAM (ram_type yang sama)
        //   CPU + VGA (tdp) → PSU (wattage yang cukup)
        //
        // Socket yang diseed:
        //   LGA1700 = Intel Gen 12/13/14 (Alder/Raptor Lake)
        //   AM5     = AMD Ryzen 7000/9000 (DDR5)
        //   AM4     = AMD Ryzen 5000 (DDR4)
        // ============================================================

        // ============================================================
        // PROCESSOR
        // ============================================================

        // --- Intel LGA1700 ---
        $this->createProduct(
            category:      $catProcessor,
            name:          'Intel Core i5-12400F',
            selling_price: 2_199_000,
            warranty:      '3 Tahun Resmi',
            description:   '6 core 12 thread, cocok untuk gaming 1080p dan produktivitas harian.',
            specs: [
                'socket'      => 'LGA1700',
                'cores'       => '6',
                'threads'     => '12',
                'base_clock'  => '2.5 GHz',
                'boost_clock' => '4.4 GHz',
                'tdp'         => '65',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 15, 'harga_beli' => 1_900_000, 'harga_jual' => 2_199_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 8,  'harga_beli' => 1_920_000, 'harga_jual' => 2_250_000],
            ]
        );

        $this->createProduct(
            category:      $catProcessor,
            name:          'Intel Core i7-13700K',
            selling_price: 5_499_000,
            warranty:      '3 Tahun Resmi',
            description:   '16 core (8P+8E) 24 thread, performa tinggi untuk gaming dan rendering.',
            specs: [
                'socket'      => 'LGA1700',
                'cores'       => '16',
                'threads'     => '24',
                'base_clock'  => '3.4 GHz',
                'boost_clock' => '5.4 GHz',
                'tdp'         => '125',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 10, 'harga_beli' => 4_800_000, 'harga_jual' => 5_499_000],
            ]
        );

        $this->createProduct(
            category:      $catProcessor,
            name:          'Intel Core i9-14900K',
            selling_price: 8_999_000,
            warranty:      '3 Tahun Resmi',
            description:   '24 core (8P+16E) flagship Intel Gen 14, raja single-thread gaming.',
            specs: [
                'socket'      => 'LGA1700',
                'cores'       => '24',
                'threads'     => '32',
                'base_clock'  => '3.2 GHz',
                'boost_clock' => '6.0 GHz',
                'tdp'         => '253',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 5, 'harga_beli' => 8_000_000, 'harga_jual' => 8_999_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 3, 'harga_beli' => 8_100_000, 'harga_jual' => 9_100_000],
            ]
        );

        // --- AMD AM5 (DDR5) ---
        $this->createProduct(
            category:      $catProcessor,
            name:          'AMD Ryzen 5 7600X',
            selling_price: 3_199_000,
            warranty:      '3 Tahun Resmi',
            description:   '6 core 12 thread, IPC terbaik di kelasnya, platform AM5 DDR5.',
            specs: [
                'socket'      => 'AM5',
                'cores'       => '6',
                'threads'     => '12',
                'base_clock'  => '4.7 GHz',
                'boost_clock' => '5.3 GHz',
                'tdp'         => '105',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 12, 'harga_beli' => 2_800_000, 'harga_jual' => 3_199_000],
            ]
        );

        $this->createProduct(
            category:      $catProcessor,
            name:          'AMD Ryzen 7 7800X3D',
            selling_price: 5_999_000,
            warranty:      '3 Tahun Resmi',
            description:   'CPU gaming terbaik saat ini berkat teknologi 3D V-Cache, platform AM5.',
            specs: [
                'socket'      => 'AM5',
                'cores'       => '8',
                'threads'     => '16',
                'base_clock'  => '4.5 GHz',
                'boost_clock' => '5.0 GHz',
                'tdp'         => '120',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 8,  'harga_beli' => 5_300_000, 'harga_jual' => 5_999_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 4,  'harga_beli' => 5_400_000, 'harga_jual' => 6_100_000],
            ]
        );

        // --- AMD AM4 (DDR4) ---
        $this->createProduct(
            category:      $catProcessor,
            name:          'AMD Ryzen 5 5600X',
            selling_price: 1_799_000,
            warranty:      '3 Tahun Resmi',
            description:   '6 core 12 thread platform AM4, opsi upgrade DDR4 yang hemat biaya.',
            specs: [
                'socket'      => 'AM4',
                'cores'       => '6',
                'threads'     => '12',
                'base_clock'  => '3.7 GHz',
                'boost_clock' => '4.6 GHz',
                'tdp'         => '65',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New',  'stock' => 10, 'harga_beli' => 1_550_000, 'harga_jual' => 1_799_000],
                ['supplier' => $supplier1, 'condition' => 'Used', 'stock' => 5,  'harga_beli' => 1_100_000, 'harga_jual' => 1_399_000],
            ]
        );

        $this->createProduct(
            category:      $catProcessor,
            name:          'AMD Ryzen 7 5800X',
            selling_price: 2_799_000,
            warranty:      '3 Tahun Resmi',
            description:   '8 core 16 thread AM4, masih relevan untuk gaming dan creative work.',
            specs: [
                'socket'      => 'AM4',
                'cores'       => '8',
                'threads'     => '16',
                'base_clock'  => '3.8 GHz',
                'boost_clock' => '4.7 GHz',
                'tdp'         => '105',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 7, 'harga_beli' => 2_400_000, 'harga_jual' => 2_799_000],
            ]
        );

        // ============================================================
        // MOTHERBOARD
        // ============================================================

        // --- Intel LGA1700 + DDR5 ---
        $this->createProduct(
            category:      $catMotherboard,
            name:          'ASUS PRIME Z790-P DDR5',
            selling_price: 3_499_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard mid-range Z790 untuk Intel Gen 12/13/14, support DDR5.',
            specs: [
                'socket'       => 'LGA1700',
                'ram_type'     => 'DDR5',
                'form_factor'  => 'ATX',
                'ram_slots'    => '4',
                'max_ram'      => '128GB',
                'pcie_version' => 'PCIe 5.0',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 8, 'harga_beli' => 3_100_000, 'harga_jual' => 3_499_000],
            ]
        );

        $this->createProduct(
            category:      $catMotherboard,
            name:          'MSI MAG Z790 TOMAHAWK DDR5',
            selling_price: 4_799_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard upper mid-range Z790, fitur lengkap, build quality solid.',
            specs: [
                'socket'       => 'LGA1700',
                'ram_type'     => 'DDR5',
                'form_factor'  => 'ATX',
                'ram_slots'    => '4',
                'max_ram'      => '192GB',
                'pcie_version' => 'PCIe 5.0',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 6, 'harga_beli' => 4_200_000, 'harga_jual' => 4_799_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 4, 'harga_beli' => 4_300_000, 'harga_jual' => 4_899_000],
            ]
        );

        $this->createProduct(
            category:      $catMotherboard,
            name:          'ASUS ROG STRIX Z790-E DDR5',
            selling_price: 7_599_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard flagship Z790 ROG, untuk build premium Intel LGA1700.',
            specs: [
                'socket'       => 'LGA1700',
                'ram_type'     => 'DDR5',
                'form_factor'  => 'ATX',
                'ram_slots'    => '4',
                'max_ram'      => '192GB',
                'pcie_version' => 'PCIe 5.0',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 4, 'harga_beli' => 6_800_000, 'harga_jual' => 7_599_000],
            ]
        );

        // --- Intel LGA1700 + DDR4 ---
        $this->createProduct(
            category:      $catMotherboard,
            name:          'Gigabyte B660M DS3H DDR4',
            selling_price: 1_699_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard entry-level B660 LGA1700 + DDR4, pilihan hemat Gen 12.',
            specs: [
                'socket'       => 'LGA1700',
                'ram_type'     => 'DDR4',
                'form_factor'  => 'mATX',
                'ram_slots'    => '2',
                'max_ram'      => '64GB',
                'pcie_version' => 'PCIe 4.0',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 10, 'harga_beli' => 1_450_000, 'harga_jual' => 1_699_000],
            ]
        );

        // --- AMD AM5 + DDR5 ---
        $this->createProduct(
            category:      $catMotherboard,
            name:          'MSI MAG X670E TOMAHAWK DDR5',
            selling_price: 4_999_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard X670E AM5 DDR5, siap untuk Ryzen 7000 series.',
            specs: [
                'socket'       => 'AM5',
                'ram_type'     => 'DDR5',
                'form_factor'  => 'ATX',
                'ram_slots'    => '4',
                'max_ram'      => '128GB',
                'pcie_version' => 'PCIe 5.0',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 7, 'harga_beli' => 4_400_000, 'harga_jual' => 4_999_000],
            ]
        );

        $this->createProduct(
            category:      $catMotherboard,
            name:          'ASUS TUF GAMING B650-PLUS DDR5',
            selling_price: 3_299_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard B650 AM5 DDR5, entry-point platform AMD Ryzen 7000.',
            specs: [
                'socket'       => 'AM5',
                'ram_type'     => 'DDR5',
                'form_factor'  => 'ATX',
                'ram_slots'    => '4',
                'max_ram'      => '128GB',
                'pcie_version' => 'PCIe 4.0',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 9,  'harga_beli' => 2_900_000, 'harga_jual' => 3_299_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 5,  'harga_beli' => 2_950_000, 'harga_jual' => 3_399_000],
            ]
        );

        // --- AMD AM4 + DDR4 ---
        $this->createProduct(
            category:      $catMotherboard,
            name:          'ASUS TUF GAMING B550M-PLUS DDR4',
            selling_price: 1_899_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard B550 mATX AM4, pilihan solid untuk Ryzen 5000.',
            specs: [
                'socket'       => 'AM4',
                'ram_type'     => 'DDR4',
                'form_factor'  => 'mATX',
                'ram_slots'    => '4',
                'max_ram'      => '128GB',
                'pcie_version' => 'PCIe 4.0',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New',  'stock' => 8,  'harga_beli' => 1_650_000, 'harga_jual' => 1_899_000],
                ['supplier' => $supplier1, 'condition' => 'Used', 'stock' => 3,  'harga_beli' => 1_100_000, 'harga_jual' => 1_399_000],
            ]
        );

        $this->createProduct(
            category:      $catMotherboard,
            name:          'MSI MAG B550 TOMAHAWK DDR4',
            selling_price: 2_199_000,
            warranty:      '3 Tahun Resmi',
            description:   'Motherboard B550 ATX AM4 populer, VRM solid untuk Ryzen 5600X/5800X.',
            specs: [
                'socket'       => 'AM4',
                'ram_type'     => 'DDR4',
                'form_factor'  => 'ATX',
                'ram_slots'    => '4',
                'max_ram'      => '128GB',
                'pcie_version' => 'PCIe 4.0',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 6, 'harga_beli' => 1_900_000, 'harga_jual' => 2_199_000],
            ]
        );

        // ============================================================
        // RAM
        // ============================================================

        // --- DDR5 ---
        $this->createProduct(
            category:      $catRam,
            name:          'Corsair Vengeance DDR5 32GB (2x16GB) 5200MHz',
            selling_price: 1_799_000,
            warranty:      '3 Tahun Resmi',
            description:   'Kit DDR5 dual channel 32GB, cocok untuk Z790/B650 platform.',
            specs: [
                'ram_type' => 'DDR5',
                'capacity' => '32',
                'speed'    => '5200',
                'latency'  => 'CL40',
                'kit'      => '2x16GB',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 20, 'harga_beli' => 1_550_000, 'harga_jual' => 1_799_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 10, 'harga_beli' => 1_570_000, 'harga_jual' => 1_850_000],
            ]
        );

        $this->createProduct(
            category:      $catRam,
            name:          'Kingston Fury Beast DDR5 32GB (2x16GB) 6000MHz',
            selling_price: 2_099_000,
            warranty:      '3 Tahun Resmi',
            description:   'DDR5 6000MHz sweet spot untuk platform Intel dan AMD AM5.',
            specs: [
                'ram_type' => 'DDR5',
                'capacity' => '32',
                'speed'    => '6000',
                'latency'  => 'CL36',
                'kit'      => '2x16GB',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 12, 'harga_beli' => 1_800_000, 'harga_jual' => 2_099_000],
            ]
        );

        $this->createProduct(
            category:      $catRam,
            name:          'G.Skill Trident Z5 RGB DDR5 64GB (2x32GB) 6000MHz',
            selling_price: 4_299_000,
            warranty:      '3 Tahun Resmi',
            description:   '64GB DDR5 untuk workstation/editing, XMP 3.0 ready.',
            specs: [
                'ram_type' => 'DDR5',
                'capacity' => '64',
                'speed'    => '6000',
                'latency'  => 'CL30',
                'kit'      => '2x32GB',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 5, 'harga_beli' => 3_800_000, 'harga_jual' => 4_299_000],
            ]
        );

        // --- DDR4 ---
        $this->createProduct(
            category:      $catRam,
            name:          'Corsair Vengeance LPX DDR4 16GB (2x8GB) 3200MHz',
            selling_price: 599_000,
            warranty:      '3 Tahun Resmi',
            description:   'Kit DDR4 dual channel 16GB paling populer, kompatibel luas.',
            specs: [
                'ram_type' => 'DDR4',
                'capacity' => '16',
                'speed'    => '3200',
                'latency'  => 'CL16',
                'kit'      => '2x8GB',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New',  'stock' => 30, 'harga_beli' => 500_000,  'harga_jual' => 599_000],
                ['supplier' => $supplier2, 'condition' => 'New',  'stock' => 20, 'harga_beli' => 510_000,  'harga_jual' => 620_000],
                ['supplier' => $supplier2, 'condition' => 'Used', 'stock' => 8,  'harga_beli' => 320_000,  'harga_jual' => 420_000],
            ]
        );

        $this->createProduct(
            category:      $catRam,
            name:          'Kingston Fury Beast DDR4 32GB (2x16GB) 3600MHz',
            selling_price: 1_099_000,
            warranty:      '3 Tahun Resmi',
            description:   '32GB DDR4 dual channel, performa gaming dan multitasking optimal.',
            specs: [
                'ram_type' => 'DDR4',
                'capacity' => '32',
                'speed'    => '3600',
                'latency'  => 'CL18',
                'kit'      => '2x16GB',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 15, 'harga_beli' => 950_000, 'harga_jual' => 1_099_000],
            ]
        );

        $this->createProduct(
            category:      $catRam,
            name:          'G.Skill Ripjaws V DDR4 16GB (2x8GB) 3600MHz',
            selling_price: 699_000,
            warranty:      '3 Tahun Resmi',
            description:   'DDR4 3600MHz heatspreader low-profile, performa tinggi harga terjangkau.',
            specs: [
                'ram_type' => 'DDR4',
                'capacity' => '16',
                'speed'    => '3600',
                'latency'  => 'CL16',
                'kit'      => '2x8GB',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 18, 'harga_beli' => 600_000, 'harga_jual' => 699_000],
            ]
        );

        // ============================================================
        // VGA / GPU
        // ============================================================
        $this->createProduct(
            category:      $catVga,
            name:          'NVIDIA GeForce RTX 4060 8GB GDDR6',
            selling_price: 5_299_000,
            warranty:      '3 Tahun Resmi',
            description:   'GPU 1080p/1440p mainstream, efisien daya, cocok untuk build budget-mid.',
            specs: [
                'tdp'          => '115',
                'vram'         => '8',
                'vram_type'    => 'GDDR6',
                'pcie_version' => 'PCIe 4.0',
                'length'       => '240',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 10, 'harga_beli' => 4_700_000, 'harga_jual' => 5_299_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 6,  'harga_beli' => 4_750_000, 'harga_jual' => 5_399_000],
            ]
        );

        $this->createProduct(
            category:      $catVga,
            name:          'NVIDIA GeForce RTX 4070 Super 12GB GDDR6X',
            selling_price: 8_999_000,
            warranty:      '3 Tahun Resmi',
            description:   'GPU 1440p raja value, performa mendekati 4080 dengan harga lebih wajar.',
            specs: [
                'tdp'          => '220',
                'vram'         => '12',
                'vram_type'    => 'GDDR6X',
                'pcie_version' => 'PCIe 4.0',
                'length'       => '285',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 7, 'harga_beli' => 8_200_000, 'harga_jual' => 8_999_000],
            ]
        );

        $this->createProduct(
            category:      $catVga,
            name:          'NVIDIA GeForce RTX 4080 Super 16GB GDDR6X',
            selling_price: 16_499_000,
            warranty:      '3 Tahun Resmi',
            description:   'GPU high-end 4K gaming, pilihan terbaik untuk build premium.',
            specs: [
                'tdp'          => '320',
                'vram'         => '16',
                'vram_type'    => 'GDDR6X',
                'pcie_version' => 'PCIe 4.0',
                'length'       => '336',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 3, 'harga_beli' => 14_900_000, 'harga_jual' => 16_499_000],
            ]
        );

        $this->createProduct(
            category:      $catVga,
            name:          'AMD Radeon RX 7600 8GB GDDR6',
            selling_price: 3_999_000,
            warranty:      '3 Tahun Resmi',
            description:   'GPU entry AMD 1080p, efisien daya, driver matang.',
            specs: [
                'tdp'          => '165',
                'vram'         => '8',
                'vram_type'    => 'GDDR6',
                'pcie_version' => 'PCIe 4.0',
                'length'       => '230',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 8, 'harga_beli' => 3_500_000, 'harga_jual' => 3_999_000],
            ]
        );

        $this->createProduct(
            category:      $catVga,
            name:          'AMD Radeon RX 7900 XTX 24GB GDDR6',
            selling_price: 15_999_000,
            warranty:      '3 Tahun Resmi',
            description:   'Flagship AMD RDNA3, 24GB VRAM untuk 4K gaming dan AI workload.',
            specs: [
                'tdp'          => '355',
                'vram'         => '24',
                'vram_type'    => 'GDDR6',
                'pcie_version' => 'PCIe 4.0',
                'length'       => '287',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 2, 'harga_beli' => 14_500_000, 'harga_jual' => 15_999_000],
            ]
        );

        // ============================================================
        // STORAGE
        // ============================================================
        $this->createProduct(
            category:      $catStorage,
            name:          'Samsung 970 EVO Plus 500GB NVMe',
            selling_price: 799_000,
            warranty:      '5 Tahun Resmi',
            description:   'SSD NVMe M.2 populer, cocok untuk OS dan game.',
            specs: [
                'interface'   => 'NVMe',
                'capacity'    => '500',
                'read_speed'  => '3500',
                'write_speed' => '3200',
                'form_factor' => 'M.2',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 25, 'harga_beli' => 680_000, 'harga_jual' => 799_000],
            ]
        );

        $this->createProduct(
            category:      $catStorage,
            name:          'Samsung 990 Pro 1TB NVMe PCIe 4.0',
            selling_price: 1_499_000,
            warranty:      '5 Tahun Resmi',
            description:   'SSD NVMe Gen 4 tercepat Samsung, perfect untuk gaming dan creative work.',
            specs: [
                'interface'   => 'NVMe',
                'capacity'    => '1000',
                'read_speed'  => '7450',
                'write_speed' => '6900',
                'form_factor' => 'M.2',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 15, 'harga_beli' => 1_300_000, 'harga_jual' => 1_499_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 8,  'harga_beli' => 1_320_000, 'harga_jual' => 1_549_000],
            ]
        );

        $this->createProduct(
            category:      $catStorage,
            name:          'WD Black SN850X 2TB NVMe PCIe 4.0',
            selling_price: 2_799_000,
            warranty:      '5 Tahun Resmi',
            description:   '2TB NVMe Gen 4, kapasitas besar untuk koleksi game besar.',
            specs: [
                'interface'   => 'NVMe',
                'capacity'    => '2000',
                'read_speed'  => '7300',
                'write_speed' => '6600',
                'form_factor' => 'M.2',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 8, 'harga_beli' => 2_450_000, 'harga_jual' => 2_799_000],
            ]
        );

        $this->createProduct(
            category:      $catStorage,
            name:          'Seagate Barracuda 2TB HDD SATA',
            selling_price: 699_000,
            warranty:      '2 Tahun Resmi',
            description:   'HDD 2TB 7200RPM, storage sekunder untuk file dan backup.',
            specs: [
                'interface'   => 'SATA',
                'capacity'    => '2000',
                'read_speed'  => '210',
                'write_speed' => '210',
                'form_factor' => '3.5"',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New',  'stock' => 20, 'harga_beli' => 590_000, 'harga_jual' => 699_000],
                ['supplier' => $supplier1, 'condition' => 'Used', 'stock' => 5,  'harga_beli' => 350_000, 'harga_jual' => 450_000],
            ]
        );

        // ============================================================
        // POWER SUPPLY
        // Wattage diisi sebagai angka (string) agar bisa dipakai
        // untuk kalkulasi estimasi daya di PC Builder.
        // ============================================================
        $this->createProduct(
            category:      $catPsu,
            name:          'Corsair CV550 550W 80+ Bronze',
            selling_price: 699_000,
            warranty:      '3 Tahun Resmi',
            description:   'PSU entry 550W untuk build budget, cocok untuk GPU mid-range.',
            specs: [
                'wattage'     => '550',
                'efficiency'  => '80+ Bronze',
                'modular'     => 'Non',
                'form_factor' => 'ATX',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 15, 'harga_beli' => 600_000, 'harga_jual' => 699_000],
            ]
        );

        $this->createProduct(
            category:      $catPsu,
            name:          'Seasonic Focus GX-750 750W 80+ Gold',
            selling_price: 1_499_000,
            warranty:      '10 Tahun Resmi',
            description:   'PSU Gold fully modular, kualitas premium, cocok untuk build mid-high.',
            specs: [
                'wattage'     => '750',
                'efficiency'  => '80+ Gold',
                'modular'     => 'Full',
                'form_factor' => 'ATX',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 10, 'harga_beli' => 1_300_000, 'harga_jual' => 1_499_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 5,  'harga_beli' => 1_320_000, 'harga_jual' => 1_549_000],
            ]
        );

        $this->createProduct(
            category:      $catPsu,
            name:          'Corsair RM850x 850W 80+ Gold',
            selling_price: 1_899_000,
            warranty:      '10 Tahun Resmi',
            description:   'PSU 850W fully modular Gold, ideal untuk RTX 4070/4080 build.',
            specs: [
                'wattage'     => '850',
                'efficiency'  => '80+ Gold',
                'modular'     => 'Full',
                'form_factor' => 'ATX',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 8, 'harga_beli' => 1_650_000, 'harga_jual' => 1_899_000],
            ]
        );

        $this->createProduct(
            category:      $catPsu,
            name:          'be quiet! Dark Power 1000W 80+ Titanium',
            selling_price: 3_999_000,
            warranty:      '10 Tahun Resmi',
            description:   'PSU 1000W Titanium untuk build flagship, zero noise, efisiensi tertinggi.',
            specs: [
                'wattage'     => '1000',
                'efficiency'  => '80+ Titanium',
                'modular'     => 'Full',
                'form_factor' => 'ATX',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 3, 'harga_beli' => 3_500_000, 'harga_jual' => 3_999_000],
            ]
        );

        // ============================================================
        // CPU COOLER
        // ============================================================
        $this->createProduct(
            category:      $catCooler,
            name:          'DeepCool AK400 Air Cooler',
            selling_price: 499_000,
            warranty:      '3 Tahun Resmi',
            description:   'Air cooler tower single tower terbaik di harganya, support multi-socket.',
            specs: [
                'socket'      => 'LGA1700,AM5,AM4',
                'height'      => '155',
                'tdp_support' => '220',
                'type'        => 'Air',
            ],
            suppliers: [
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 20, 'harga_beli' => 420_000, 'harga_jual' => 499_000],
            ]
        );

        $this->createProduct(
            category:      $catCooler,
            name:          'Noctua NH-D15 Chromax Black',
            selling_price: 1_499_000,
            warranty:      '6 Tahun Resmi',
            description:   'Air cooler dual tower terbaik di dunia, performa setara AIO 280mm.',
            specs: [
                'socket'      => 'LGA1700,AM5,AM4',
                'height'      => '165',
                'tdp_support' => '250',
                'type'        => 'Air',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 8, 'harga_beli' => 1_300_000, 'harga_jual' => 1_499_000],
            ]
        );

        $this->createProduct(
            category:      $catCooler,
            name:          'Corsair H150i Elite LCD 360mm AIO',
            selling_price: 2_999_000,
            warranty:      '5 Tahun Resmi',
            description:   'AIO liquid cooler 360mm, tampilan LCD, untuk CPU TDP tinggi.',
            specs: [
                'socket'      => 'LGA1700,AM5,AM4',
                'height'      => '360',
                'tdp_support' => '300',
                'type'        => 'AIO',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 5, 'harga_beli' => 2_600_000, 'harga_jual' => 2_999_000],
            ]
        );

        // ============================================================
        // CASING
        // ============================================================
        $this->createProduct(
            category:      $catCasing,
            name:          'Lian Li Lancool 216 RGB',
            selling_price: 1_299_000,
            warranty:      '1 Tahun Resmi',
            description:   'Casing ATX airflow terbaik dengan 2 fan ARGB bawaan.',
            specs: [
                'form_factor'            => 'ATX',
                'max_gpu_length'         => '400',
                'max_cpu_cooler_height'  => '176',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 10, 'harga_beli' => 1_100_000, 'harga_jual' => 1_299_000],
                ['supplier' => $supplier2, 'condition' => 'New', 'stock' => 5,  'harga_beli' => 1_120_000, 'harga_jual' => 1_349_000],
            ]
        );

        $this->createProduct(
            category:      $catCasing,
            name:          'Fractal Design Define 7',
            selling_price: 2_499_000,
            warranty:      '2 Tahun Resmi',
            description:   'Casing silent premium ATX, desain minimalis, banyak ruang penyimpanan.',
            specs: [
                'form_factor'            => 'ATX',
                'max_gpu_length'         => '440',
                'max_cpu_cooler_height'  => '185',
            ],
            suppliers: [
                ['supplier' => $supplier1, 'condition' => 'New', 'stock' => 4, 'harga_beli' => 2_200_000, 'harga_jual' => 2_499_000],
            ]
        );
    }

    // ============================================================
    // HELPER — biar tidak perlu nulis ulang logic berulang
    // ============================================================
    private function createProduct(
        Category $category,
        string   $name,
        int      $selling_price,
        string   $warranty,
        string   $description,
        array    $specs,
        array    $suppliers
    ): void {
        // 1. Buat produk
        $product = Product::create([
            'category_id'   => $category->id,
            'name'          => $name,
            'selling_price' => $selling_price,
            'warranty'      => $warranty,
            'description'   => $description,
        ]);

        // 2. Simpan spesifikasi
        foreach ($specs as $key => $value) {
            $product->specifications()->create([
                'spec_key'   => $key,
                'spec_value' => $value,
            ]);
        }

        // 3. Attach ke supplier
        foreach ($suppliers as $sup) {
            $product->suppliers()->attach($sup['supplier']->id, [
                'condition'          => $sup['condition'],
                'stock'              => $sup['stock'],
                'harga_beli'         => $sup['harga_beli'],
                'harga_jual_manual'  => $sup['harga_jual'],
                'entry_date'         => now(),
            ]);
        }
    }
}
