<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SpecValuePreset;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // Users
        // =====================================================================

        $staffUser = User::create([
            'name' => 'Staff',
            'email' => 'staff@gmail.com',
            'password' => Hash::make('123'),
            'role' => 'staff',
        ]);

        $ownerUser = User::create([
            'name' => 'Owner User',
            'email' => 'owner@gmail.com',
            'password' => Hash::make('123'),
            'role' => 'owner',
        ]);

        // =====================================================================
        // Categories
        // =====================================================================

        foreach ([
            'Processor',
            'Motherboard',
            'RAM',
            'VGA',
            'Storage',
            'Power Supply',
            'Casing',
            'CPU Cooler',
            'Monitor',
            'Keyboard',
            'Mouse',
            'Audio & Speaker',
            'Webcam',
            'Networking (Wifi/LAN)',
            'Cables & Adapters',
            'Software & OS',
            'Thermal Paste',
            'Tools & Cleaning Kit',
            'Gaming Chair & Desk',
            'Lainnya',
        ] as $categoryName) {
            Category::create(['name' => $categoryName]);
        }

        // =====================================================================
        // Suppliers
        // =====================================================================

        $supplier1 = Supplier::create([
            'nama_supplier' => 'PT Sumber Teknologi',
            'alamat' => 'Jakarta',
        ]);

        $supplier2 = Supplier::create([
            'nama_supplier' => 'CV Mega Komputer',
            'alamat' => 'Surabaya',
        ]);

        // =====================================================================
        // Customers
        // =====================================================================

        Customer::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@email.com',
            'phone' => '081234567890',
            'address' => 'Jakarta Selatan',
        ]);

        Customer::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@email.com',
            'phone' => '081234567891',
            'address' => 'Bandung',
        ]);

        // =====================================================================
        // Spec Value Presets
        // Ini yang akan mengisi dropdown di halaman input produk.
        // Key harus sesuai persis dengan yang ada di config/product_specs.php
        // =====================================================================

        $presets = [
            // --- socket_type (dipakai Processor & Motherboard) ---
            'socket_type' => ['LGA1700', 'LGA1851', 'AM5', 'AM4', 'LGA1200', 'TR5'],

            // --- chipset_support (Processor) & chipset (Motherboard) ---
            'chipset_support' => ['Z790', 'B760', 'H770', 'Z690', 'X670E', 'X670', 'B650E', 'B650', 'A620'],
            'chipset'         => ['Z790', 'B760', 'H770', 'Z690', 'X670E', 'X670', 'B650E', 'B650', 'A620'],

            // --- ram_type_support (Processor) ---
            'ram_type_support' => ['DDR4', 'DDR5', 'DDR4/DDR5'],

            // --- tdp_watt (Processor) ---
            'tdp_watt' => ['35', '45', '65', '88', '105', '125', '170', '253'],

            // --- ram_type_slot (Motherboard) & ram_type (RAM) ---
            'ram_type_slot' => ['DDR4', 'DDR5'],
            'ram_type'      => ['DDR4', 'DDR5'],

            // --- form_factor (Motherboard, PSU, Storage) ---
            'form_factor' => ['ATX', 'M-ATX', 'ITX', 'E-ATX', 'SFX', 'SFX-L', 'M.2', '2.5', '3.5'],

            // --- speed_mhz (RAM) ---
            'speed_mhz' => ['3200', '3600', '4000', '4800', '5200', '5600', '6000', '6400', '6800'],

            // --- capacity_gb (RAM & Storage) ---
            'capacity_gb' => ['8', '16', '32', '64', '128', '250', '500', '512', '1000', '2000', '4000'],

            // --- pcie_version (GPU) ---
            'pcie_version' => ['PCIe 3.0', 'PCIe 4.0', 'PCIe 5.0'],

            // --- min_psu_watt (GPU) ---
            'min_psu_watt' => ['450', '550', '650', '750', '850', '1000'],

            // --- length_mm (GPU) ---
            'length_mm' => ['170', '200', '240', '270', '300', '320', '336', '340', '360'],

            // --- total_wattage (PSU) ---
            'total_wattage' => ['450', '550', '650', '750', '850', '1000', '1200', '1600'],

            // --- efficiency_rating (PSU) ---
            'efficiency_rating' => [
                '80+ WHITE', '80+ BRONZE', '80+ SILVER',
                '80+ GOLD', '80+ PLATINUM', '80+ TITANIUM',
            ],

            // --- supported_motherboard_sizes (Casing) ---
            'supported_motherboard_sizes' => [
                'ITX',
                'M-ATX',
                'ATX',
                'M-ATX, ITX',
                'ATX, M-ATX, ITX',
                'E-ATX, ATX, M-ATX, ITX',
            ],

            // --- max_gpu_length_mm (Casing) ---
            'max_gpu_length_mm' => ['240', '280', '300', '320', '340', '360', '400', '420', '450'],

            // --- max_cpu_cooler_height_mm (Casing) ---
            'max_cpu_cooler_height_mm' => ['130', '145', '155', '160', '165', '170', '185'],

            // --- interface_type (Storage) ---
            'interface_type' => ['NVME', 'SATA', 'SATA III', 'USB'],
        ];

        foreach ($presets as $key => $values) {
            foreach ($values as $value) {
                SpecValuePreset::firstOrCreate([
                    'spec_key' => $key,
                    'spec_value' => $value,
                ]);
            }
        }

        // =====================================================================
        // Products
        // PENTING: key specs harus pakai canonical key dari config/product_specs.php
        // bukan display label seperti 'Socket', 'Form Factor', dll.
        // =====================================================================

        $products = [
            [
                'category' => 'Processor',
                'brand' => 'Intel',
                'name' => 'Intel Core i9-14900K',
                'letak_barang' => 'Rak A1',
                'supplier_id' => $supplier1->id,
                'pemodal_user_id' => $ownerUser->id,
                'price' => 9500000,
                'cost' => 8800000,
                'specs' => [
                    'socket_type'      => 'LGA1700',
                    'chipset_support'  => 'Z790',
                    'ram_type_support' => 'DDR4/DDR5',
                    'tdp_watt'         => '125',
                ],
                'extra_specs' => [
                    ['key' => 'cores',      'value' => '24'],
                    ['key' => 'threads',    'value' => '32'],
                    ['key' => 'base_clock', 'value' => '3.2 GHz'],
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand' => 'ASUS',
                'name' => 'ASUS ROG MAXIMUS Z790 HERO',
                'letak_barang' => 'Rak A2',
                'supplier_id' => $supplier1->id,
                'pemodal_user_id' => $ownerUser->id,
                'price' => 11000000,
                'cost' => 10200000,
                'specs' => [
                    'socket_type'   => 'LGA1700',
                    'ram_type_slot' => 'DDR5',
                    'form_factor'   => 'ATX',
                    'chipset'       => 'Z790',
                ],
                'extra_specs' => [
                    ['key' => 'pcie_slots', 'value' => 'PCIe 5.0 x16'],
                ],
            ],
            [
                'category' => 'RAM',
                'brand' => 'Corsair',
                'name' => 'Corsair Dominator Titanium 64GB DDR5 6000MHz',
                'letak_barang' => 'Rak B1',
                'supplier_id' => $supplier2->id,
                'pemodal_user_id' => $staffUser->id,
                'price' => 4500000,
                'cost' => 4100000,
                'specs' => [
                    'ram_type'    => 'DDR5',
                    'speed_mhz'   => '6000',
                    'capacity_gb' => '64',
                ],
                'extra_specs' => [
                    ['key' => 'rgb', 'value' => 'Yes'],
                ],
            ],
            [
                'category' => 'VGA',
                'brand' => 'NVIDIA',
                'name' => 'NVIDIA RTX 4090 Founders Edition',
                'letak_barang' => 'Rak C1',
                'supplier_id' => $supplier2->id,
                'pemodal_user_id' => $ownerUser->id,
                'price' => 32000000,
                'cost' => 29500000,
                'specs' => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '850',
                    'length_mm'    => '336',
                ],
                'extra_specs' => [
                    ['key' => 'vram',         'value' => '24GB GDDR6X'],
                    ['key' => 'architecture', 'value' => 'Ada Lovelace'],
                ],
            ],
            [
                'category' => 'Storage',
                'brand' => 'Samsung',
                'name' => 'Samsung 990 Pro 2TB NVMe Gen4',
                'letak_barang' => 'Rak B2',
                'supplier_id' => $supplier1->id,
                'pemodal_user_id' => $staffUser->id,
                'price' => 2800000,
                'cost' => 2500000,
                'specs' => [
                    'form_factor'    => 'M.2',
                    'interface_type' => 'NVME',
                    'capacity_gb'    => '2000',
                ],
                'extra_specs' => [
                    ['key' => 'read_speed',  'value' => '7450 MB/s'],
                    ['key' => 'write_speed', 'value' => '6900 MB/s'],
                ],
            ],
            [
                'category' => 'Power Supply',
                'brand' => 'ROG',
                'name' => 'ROG Thor 1200W Platinum II',
                'letak_barang' => 'Rak D1',
                'supplier_id' => $supplier2->id,
                'pemodal_user_id' => $ownerUser->id,
                'price' => 5500000,
                'cost' => 5100000,
                'specs' => [
                    'total_wattage'     => '1200',
                    'efficiency_rating' => '80+ PLATINUM',
                    'form_factor'       => 'ATX',
                ],
                'extra_specs' => [
                    ['key' => 'modular', 'value' => 'Full'],
                ],
            ],
            [
                'category' => 'Casing',
                'brand' => 'LIAN LI',
                'name' => 'LIAN LI O11 Dynamic EVO',
                'letak_barang' => 'Gudang 1',
                'supplier_id' => $supplier1->id,
                'pemodal_user_id' => $ownerUser->id,
                'price' => 2700000,
                'cost' => 2400000,
                'specs' => [
                    'supported_motherboard_sizes' => 'ATX, M-ATX, ITX',
                    'max_gpu_length_mm'           => '420',
                    'max_cpu_cooler_height_mm'    => '167',
                ],
                'extra_specs' => [
                    ['key' => 'type',  'value' => 'Mid Tower'],
                    ['key' => 'color', 'value' => 'Black'],
                ],
            ],
            [
                'category' => 'CPU Cooler',
                'brand' => 'NZXT',
                'name' => 'NZXT Kraken Elite 360 RGB',
                'letak_barang' => 'Rak D2',
                'supplier_id' => $supplier2->id,
                'pemodal_user_id' => $staffUser->id,
                'price' => 4200000,
                'cost' => 3800000,
                'specs' => [],
                'extra_specs' => [
                    ['key' => 'radiator',    'value' => '360mm'],
                    ['key' => 'lcd_display', 'value' => 'Yes'],
                    ['key' => 'fans',        'value' => '3x 120mm RGB'],
                ],
            ],
        ];

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->firstOrFail();

            // Gabungkan specs + extra_specs untuk technical_specs JSON
            $allSpecs = array_merge(
                $item['specs'],
                collect($item['extra_specs'] ?? [])
                    ->filter(fn($s) => $s['key'] !== '')
                    ->mapWithKeys(fn($s) => [$s['key'] => $s['value']])
                    ->all()
            );

            $product = Product::create([
                'category_id'    => $category->id,
                'brand'          => $item['brand'],
                'name'           => $item['name'],
                'letak_barang'   => $item['letak_barang'],
                'description'    => 'High-end component for PC Enthusiast',
                'technical_specs' => $allSpecs,  // simpan ke JSON column juga
            ]);

            // Simpan ke product_specifications dengan canonical key
            foreach ($item['specs'] as $key => $value) {
                $product->specifications()->create([
                    'spec_key'   => $key,
                    'spec_value' => $value,
                ]);
            }

            // Simpan extra specs ke product_specifications juga
            foreach ($item['extra_specs'] ?? [] as $spec) {
                if (trim($spec['key']) === '') {
                    continue;
                }

                $product->specifications()->create([
                    'spec_key'   => $spec['key'],
                    'spec_value' => $spec['value'],
                ]);
            }

            $product->suppliers()->attach($item['supplier_id'], [
                'condition'       => 'New',
                'stock'           => 15,
                'harga_beli'      => $item['cost'],
                'harga_jual_manual' => $item['price'],
                'pemodal_user_id' => $item['pemodal_user_id'],
                'entry_date'      => now(),
            ]);
        }
    }
}
