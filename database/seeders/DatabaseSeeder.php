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
        
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
