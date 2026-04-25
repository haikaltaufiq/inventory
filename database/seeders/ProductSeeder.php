<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ProductSeeder
 *
 * Seeder produk lengkap untuk testing simulasi rakit PC.
 * Mencakup berbagai kombinasi socket, RAM type, form factor, dll.
 *
 * Cara pakai:
 *   php artisan db:seed --class=ProductSeeder
 *
 * Atau daftarkan di DatabaseSeeder:
 *   $this->call(ProductSeeder::class);
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();
        $staff = User::where('role', 'staff')->first();

        $sup1 = Supplier::first();
        $sup2 = Supplier::skip(1)->first();

        // Fallback kalau supplier belum ada
        if (! $sup1 || ! $sup2) {
            $this->command->warn('Supplier belum ada. Jalankan DatabaseSeeder terlebih dahulu.');
            return;
        }

        $products = [

            // =================================================================
            // MOTHERBOARD
            // Variasi: LGA1700/DDR5, LGA1700/DDR4, AM5/DDR5, AM4/DDR4
            // Form factor: ATX, M-ATX, ITX
            // =================================================================

            [
                'category' => 'Motherboard',
                'brand'    => 'ASUS',
                'name'     => 'ASUS ROG MAXIMUS Z790 HERO',
                'letak'    => 'Rak A1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 11000000,
                'cost'     => 10200000,
                'specs'    => [
                    'socket_type'   => 'LGA1700',
                    'ram_type_slot' => 'DDR5',
                    'form_factor'   => 'ATX',
                    'chipset'       => 'Z790',
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand'    => 'MSI',
                'name'     => 'MSI MAG B760 TOMAHAWK DDR4',
                'letak'    => 'Rak A1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 2800000,
                'cost'     => 2500000,
                'specs'    => [
                    'socket_type'   => 'LGA1700',
                    'ram_type_slot' => 'DDR4',
                    'form_factor'   => 'ATX',
                    'chipset'       => 'B760',
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand'    => 'Gigabyte',
                'name'     => 'Gigabyte B760M DS3H DDR4',
                'letak'    => 'Rak A1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1850000,
                'cost'     => 1650000,
                'specs'    => [
                    'socket_type'   => 'LGA1700',
                    'ram_type_slot' => 'DDR4',
                    'form_factor'   => 'M-ATX',
                    'chipset'       => 'B760',
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand'    => 'ASUS',
                'name'     => 'ASUS ROG STRIX Z790-I GAMING WIFI',
                'letak'    => 'Rak A1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 5500000,
                'cost'     => 5000000,
                'specs'    => [
                    'socket_type'   => 'LGA1700',
                    'ram_type_slot' => 'DDR5',
                    'form_factor'   => 'ITX',
                    'chipset'       => 'Z790',
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand'    => 'MSI',
                'name'     => 'MSI MEG X670E ACE',
                'letak'    => 'Rak A2',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 8500000,
                'cost'     => 7900000,
                'specs'    => [
                    'socket_type'   => 'AM5',
                    'ram_type_slot' => 'DDR5',
                    'form_factor'   => 'ATX',
                    'chipset'       => 'X670E',
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand'    => 'Gigabyte',
                'name'     => 'Gigabyte B650 AORUS Elite AX',
                'letak'    => 'Rak A2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 3200000,
                'cost'     => 2900000,
                'specs'    => [
                    'socket_type'   => 'AM5',
                    'ram_type_slot' => 'DDR5',
                    'form_factor'   => 'ATX',
                    'chipset'       => 'B650',
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand'    => 'ASRock',
                'name'     => 'ASRock B650M Pro RS',
                'letak'    => 'Rak A2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 2100000,
                'cost'     => 1900000,
                'specs'    => [
                    'socket_type'   => 'AM5',
                    'ram_type_slot' => 'DDR5',
                    'form_factor'   => 'M-ATX',
                    'chipset'       => 'B650',
                ],
            ],
            [
                'category' => 'Motherboard',
                'brand'    => 'ASUS',
                'name'     => 'ASUS TUF Gaming B450M-PLUS II',
                'letak'    => 'Rak A3',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1350000,
                'cost'     => 1200000,
                'specs'    => [
                    'socket_type'   => 'AM4',
                    'ram_type_slot' => 'DDR4',
                    'form_factor'   => 'M-ATX',
                    'chipset'       => 'B450',
                ],
            ],

            // =================================================================
            // PROCESSOR
            // Variasi: LGA1700, AM5, AM4
            // =================================================================

            [
                'category' => 'Processor',
                'brand'    => 'Intel',
                'name'     => 'Intel Core i9-14900K',
                'letak'    => 'Rak B1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 9500000,
                'cost'     => 8800000,
                'specs'    => [
                    'socket_type'      => 'LGA1700',
                    'chipset_support'  => 'Z790',
                    'ram_type_support' => 'DDR4/DDR5',
                    'tdp_watt'         => '125',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'Intel',
                'name'     => 'Intel Core i7-13700K',
                'letak'    => 'Rak B1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 6200000,
                'cost'     => 5700000,
                'specs'    => [
                    'socket_type'      => 'LGA1700',
                    'chipset_support'  => 'Z790',
                    'ram_type_support' => 'DDR4/DDR5',
                    'tdp_watt'         => '125',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'Intel',
                'name'     => 'Intel Core i5-13600K',
                'letak'    => 'Rak B1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 3800000,
                'cost'     => 3400000,
                'specs'    => [
                    'socket_type'      => 'LGA1700',
                    'chipset_support'  => 'B760',
                    'ram_type_support' => 'DDR4/DDR5',
                    'tdp_watt'         => '125',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'Intel',
                'name'     => 'Intel Core i3-13100',
                'letak'    => 'Rak B1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1900000,
                'cost'     => 1700000,
                'specs'    => [
                    'socket_type'      => 'LGA1700',
                    'chipset_support'  => 'B760',
                    'ram_type_support' => 'DDR4/DDR5',
                    'tdp_watt'         => '60',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'AMD',
                'name'     => 'AMD Ryzen 9 7950X',
                'letak'    => 'Rak B2',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 10500000,
                'cost'     => 9700000,
                'specs'    => [
                    'socket_type'      => 'AM5',
                    'chipset_support'  => 'X670E',
                    'ram_type_support' => 'DDR5',
                    'tdp_watt'         => '170',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'AMD',
                'name'     => 'AMD Ryzen 7 7700X',
                'letak'    => 'Rak B2',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 4800000,
                'cost'     => 4300000,
                'specs'    => [
                    'socket_type'      => 'AM5',
                    'chipset_support'  => 'B650',
                    'ram_type_support' => 'DDR5',
                    'tdp_watt'         => '105',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'AMD',
                'name'     => 'AMD Ryzen 5 7600X',
                'letak'    => 'Rak B2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 3100000,
                'cost'     => 2800000,
                'specs'    => [
                    'socket_type'      => 'AM5',
                    'chipset_support'  => 'B650',
                    'ram_type_support' => 'DDR5',
                    'tdp_watt'         => '105',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'AMD',
                'name'     => 'AMD Ryzen 5 5600X',
                'letak'    => 'Rak B3',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1950000,
                'cost'     => 1750000,
                'specs'    => [
                    'socket_type'      => 'AM4',
                    'chipset_support'  => 'B450',
                    'ram_type_support' => 'DDR4',
                    'tdp_watt'         => '65',
                ],
            ],
            [
                'category' => 'Processor',
                'brand'    => 'AMD',
                'name'     => 'AMD Ryzen 7 5800X',
                'letak'    => 'Rak B3',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 2900000,
                'cost'     => 2600000,
                'specs'    => [
                    'socket_type'      => 'AM4',
                    'chipset_support'  => 'B450',
                    'ram_type_support' => 'DDR4',
                    'tdp_watt'         => '105',
                ],
            ],

            // =================================================================
            // RAM
            // Variasi: DDR4, DDR5 — berbagai speed dan kapasitas
            // =================================================================

            [
                'category' => 'RAM',
                'brand'    => 'Corsair',
                'name'     => 'Corsair Vengeance DDR5 32GB 6000MHz',
                'letak'    => 'Rak C1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1850000,
                'cost'     => 1650000,
                'specs'    => [
                    'ram_type'    => 'DDR5',
                    'speed_mhz'   => '6000',
                    'capacity_gb' => '32',
                ],
            ],
            [
                'category' => 'RAM',
                'brand'    => 'Corsair',
                'name'     => 'Corsair Dominator Titanium 64GB DDR5 6000MHz',
                'letak'    => 'Rak C1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 4500000,
                'cost'     => 4100000,
                'specs'    => [
                    'ram_type'    => 'DDR5',
                    'speed_mhz'   => '6000',
                    'capacity_gb' => '64',
                ],
            ],
            [
                'category' => 'RAM',
                'brand'    => 'G.Skill',
                'name'     => 'G.Skill Trident Z5 RGB 32GB DDR5 5600MHz',
                'letak'    => 'Rak C1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1600000,
                'cost'     => 1400000,
                'specs'    => [
                    'ram_type'    => 'DDR5',
                    'speed_mhz'   => '5600',
                    'capacity_gb' => '32',
                ],
            ],
            [
                'category' => 'RAM',
                'brand'    => 'Kingston',
                'name'     => 'Kingston Fury Beast DDR5 16GB 4800MHz',
                'letak'    => 'Rak C1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 750000,
                'cost'     => 650000,
                'specs'    => [
                    'ram_type'    => 'DDR5',
                    'speed_mhz'   => '4800',
                    'capacity_gb' => '16',
                ],
            ],
            [
                'category' => 'RAM',
                'brand'    => 'G.Skill',
                'name'     => 'G.Skill Ripjaws V 32GB DDR4 3600MHz',
                'letak'    => 'Rak C2',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 950000,
                'cost'     => 850000,
                'specs'    => [
                    'ram_type'    => 'DDR4',
                    'speed_mhz'   => '3600',
                    'capacity_gb' => '32',
                ],
            ],
            [
                'category' => 'RAM',
                'brand'    => 'Corsair',
                'name'     => 'Corsair Vengeance LPX 16GB DDR4 3200MHz',
                'letak'    => 'Rak C2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 580000,
                'cost'     => 500000,
                'specs'    => [
                    'ram_type'    => 'DDR4',
                    'speed_mhz'   => '3200',
                    'capacity_gb' => '16',
                ],
            ],
            [
                'category' => 'RAM',
                'brand'    => 'Kingston',
                'name'     => 'Kingston Fury Beast DDR4 8GB 3200MHz',
                'letak'    => 'Rak C2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 320000,
                'cost'     => 280000,
                'specs'    => [
                    'ram_type'    => 'DDR4',
                    'speed_mhz'   => '3200',
                    'capacity_gb' => '8',
                ],
            ],

            // =================================================================
            // VGA / GPU
            // Variasi: budget, mid-range, high-end
            // =================================================================

            [
                'category' => 'VGA',
                'brand'    => 'NVIDIA',
                'name'     => 'NVIDIA RTX 4090 Founders Edition',
                'letak'    => 'Rak D1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 32000000,
                'cost'     => 29500000,
                'specs'    => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '850',
                    'length_mm'    => '336',
                ],
            ],
            [
                'category' => 'VGA',
                'brand'    => 'NVIDIA',
                'name'     => 'ASUS TUF Gaming RTX 4080 Super OC',
                'letak'    => 'Rak D1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 18500000,
                'cost'     => 17000000,
                'specs'    => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '750',
                    'length_mm'    => '340',
                ],
            ],
            [
                'category' => 'VGA',
                'brand'    => 'NVIDIA',
                'name'     => 'MSI Gaming X Trio RTX 4070 Ti Super',
                'letak'    => 'Rak D1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 12500000,
                'cost'     => 11500000,
                'specs'    => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '700',
                    'length_mm'    => '320',
                ],
            ],
            [
                'category' => 'VGA',
                'brand'    => 'NVIDIA',
                'name'     => 'Gigabyte RTX 4070 Super Windforce OC',
                'letak'    => 'Rak D2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 8900000,
                'cost'     => 8200000,
                'specs'    => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '650',
                    'length_mm'    => '300',
                ],
            ],
            [
                'category' => 'VGA',
                'brand'    => 'NVIDIA',
                'name'     => 'ASUS Dual RTX 4060 OC Edition',
                'letak'    => 'Rak D2',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 4800000,
                'cost'     => 4400000,
                'specs'    => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '550',
                    'length_mm'    => '240',
                ],
            ],
            [
                'category' => 'VGA',
                'brand'    => 'AMD',
                'name'     => 'Sapphire Pulse RX 7900 XTX',
                'letak'    => 'Rak D1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 16500000,
                'cost'     => 15200000,
                'specs'    => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '800',
                    'length_mm'    => '320',
                ],
            ],
            [
                'category' => 'VGA',
                'brand'    => 'AMD',
                'name'     => 'PowerColor Hellhound RX 7700 XT',
                'letak'    => 'Rak D2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 5800000,
                'cost'     => 5300000,
                'specs'    => [
                    'pcie_version' => 'PCIe 4.0',
                    'min_psu_watt' => '650',
                    'length_mm'    => '270',
                ],
            ],

            // =================================================================
            // STORAGE
            // Variasi: NVMe M.2, SATA SSD, HDD
            // =================================================================

            [
                'category' => 'Storage',
                'brand'    => 'Samsung',
                'name'     => 'Samsung 990 Pro 2TB NVMe Gen4',
                'letak'    => 'Rak E1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 2800000,
                'cost'     => 2500000,
                'specs'    => [
                    'form_factor'    => 'M.2',
                    'interface_type' => 'NVME',
                    'capacity_gb'    => '2000',
                ],
            ],
            [
                'category' => 'Storage',
                'brand'    => 'Samsung',
                'name'     => 'Samsung 980 Pro 1TB NVMe Gen4',
                'letak'    => 'Rak E1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1400000,
                'cost'     => 1250000,
                'specs'    => [
                    'form_factor'    => 'M.2',
                    'interface_type' => 'NVME',
                    'capacity_gb'    => '1000',
                ],
            ],
            [
                'category' => 'Storage',
                'brand'    => 'WD',
                'name'     => 'WD Black SN850X 1TB NVMe Gen4',
                'letak'    => 'Rak E1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1350000,
                'cost'     => 1200000,
                'specs'    => [
                    'form_factor'    => 'M.2',
                    'interface_type' => 'NVME',
                    'capacity_gb'    => '1000',
                ],
            ],
            [
                'category' => 'Storage',
                'brand'    => 'Kingston',
                'name'     => 'Kingston NV2 500GB NVMe Gen4',
                'letak'    => 'Rak E1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 480000,
                'cost'     => 420000,
                'specs'    => [
                    'form_factor'    => 'M.2',
                    'interface_type' => 'NVME',
                    'capacity_gb'    => '500',
                ],
            ],
            [
                'category' => 'Storage',
                'brand'    => 'Samsung',
                'name'     => 'Samsung 870 EVO 1TB SATA SSD',
                'letak'    => 'Rak E2',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1100000,
                'cost'     => 980000,
                'specs'    => [
                    'form_factor'    => '2.5',
                    'interface_type' => 'SATA',
                    'capacity_gb'    => '1000',
                ],
            ],
            [
                'category' => 'Storage',
                'brand'    => 'WD',
                'name'     => 'WD Blue 4TB HDD 5400RPM',
                'letak'    => 'Rak E2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1200000,
                'cost'     => 1050000,
                'specs'    => [
                    'form_factor'    => '3.5',
                    'interface_type' => 'SATA',
                    'capacity_gb'    => '4000',
                ],
            ],

            // =================================================================
            // POWER SUPPLY
            // Variasi: wattage 550W - 1600W
            // =================================================================

            [
                'category' => 'Power Supply',
                'brand'    => 'Seasonic',
                'name'     => 'Seasonic Focus GX-850 Gold',
                'letak'    => 'Rak F1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 2200000,
                'cost'     => 2000000,
                'specs'    => [
                    'total_wattage'     => '850',
                    'efficiency_rating' => '80+ GOLD',
                    'form_factor'       => 'ATX',
                ],
            ],
            [
                'category' => 'Power Supply',
                'brand'    => 'ROG',
                'name'     => 'ROG Thor 1200W Platinum II',
                'letak'    => 'Rak F1',
                'supplier' => $sup2,
                'pemodal'  => $owner,
                'price'    => 5500000,
                'cost'     => 5100000,
                'specs'    => [
                    'total_wattage'     => '1200',
                    'efficiency_rating' => '80+ PLATINUM',
                    'form_factor'       => 'ATX',
                ],
            ],
            [
                'category' => 'Power Supply',
                'brand'    => 'Corsair',
                'name'     => 'Corsair RM750x Gold',
                'letak'    => 'Rak F1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1800000,
                'cost'     => 1600000,
                'specs'    => [
                    'total_wattage'     => '750',
                    'efficiency_rating' => '80+ GOLD',
                    'form_factor'       => 'ATX',
                ],
            ],
            [
                'category' => 'Power Supply',
                'brand'    => 'Corsair',
                'name'     => 'Corsair CV550 Bronze',
                'letak'    => 'Rak F2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 750000,
                'cost'     => 650000,
                'specs'    => [
                    'total_wattage'     => '550',
                    'efficiency_rating' => '80+ BRONZE',
                    'form_factor'       => 'ATX',
                ],
            ],
            [
                'category' => 'Power Supply',
                'brand'    => 'Seasonic',
                'name'     => 'Seasonic Focus PX-1000 Platinum',
                'letak'    => 'Rak F1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 3200000,
                'cost'     => 2900000,
                'specs'    => [
                    'total_wattage'     => '1000',
                    'efficiency_rating' => '80+ PLATINUM',
                    'form_factor'       => 'ATX',
                ],
            ],
            [
                'category' => 'Power Supply',
                'brand'    => 'Cooler Master',
                'name'     => 'Cooler Master MWE Gold 650W',
                'letak'    => 'Rak F2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 980000,
                'cost'     => 870000,
                'specs'    => [
                    'total_wattage'     => '650',
                    'efficiency_rating' => '80+ GOLD',
                    'form_factor'       => 'ATX',
                ],
            ],

            // =================================================================
            // CASING
            // Variasi: ATX, M-ATX, ITX
            // =================================================================

            [
                'category' => 'Casing',
                'brand'    => 'LIAN LI',
                'name'     => 'LIAN LI O11 Dynamic EVO',
                'letak'    => 'Gudang 1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 2700000,
                'cost'     => 2400000,
                'specs'    => [
                    'supported_motherboard_sizes' => 'ATX, M-ATX, ITX',
                    'max_gpu_length_mm'           => '420',
                    'max_cpu_cooler_height_mm'    => '167',
                ],
            ],
            [
                'category' => 'Casing',
                'brand'    => 'NZXT',
                'name'     => 'NZXT H7 Flow RGB',
                'letak'    => 'Gudang 1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 2200000,
                'cost'     => 1950000,
                'specs'    => [
                    'supported_motherboard_sizes' => 'ATX, M-ATX, ITX',
                    'max_gpu_length_mm'           => '400',
                    'max_cpu_cooler_height_mm'    => '185',
                ],
            ],
            [
                'category' => 'Casing',
                'brand'    => 'Cooler Master',
                'name'     => 'Cooler Master TD500 Mesh V2',
                'letak'    => 'Gudang 1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1350000,
                'cost'     => 1200000,
                'specs'    => [
                    'supported_motherboard_sizes' => 'ATX, M-ATX',
                    'max_gpu_length_mm'           => '360',
                    'max_cpu_cooler_height_mm'    => '165',
                ],
            ],
            [
                'category' => 'Casing',
                'brand'    => 'Fractal',
                'name'     => 'Fractal Design Pop Mini Air',
                'letak'    => 'Gudang 1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 1100000,
                'cost'     => 970000,
                'specs'    => [
                    'supported_motherboard_sizes' => 'M-ATX, ITX',
                    'max_gpu_length_mm'           => '320',
                    'max_cpu_cooler_height_mm'    => '155',
                ],
            ],
            [
                'category' => 'Casing',
                'brand'    => 'LIAN LI',
                'name'     => 'LIAN LI TU150 ITX',
                'letak'    => 'Gudang 1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1650000,
                'cost'     => 1450000,
                'specs'    => [
                    'supported_motherboard_sizes' => 'ITX',
                    'max_gpu_length_mm'           => '300',
                    'max_cpu_cooler_height_mm'    => '130',
                ],
            ],

            // =================================================================
            // CPU COOLER
            // Variasi: socket LGA1700, AM5, AM4 + AIO dan Air cooler
            // =================================================================

            [
                'category' => 'CPU Cooler',
                'brand'    => 'NZXT',
                'name'     => 'NZXT Kraken Elite 360 RGB',
                'letak'    => 'Rak G1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 4200000,
                'cost'     => 3800000,
                'specs'    => [
                    'socket_type' => 'LGA1700',
                ],
            ],
            [
                'category' => 'CPU Cooler',
                'brand'    => 'Cooler Master',
                'name'     => 'Cooler Master Hyper 212 Halo Black',
                'letak'    => 'Rak G1',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 550000,
                'cost'     => 480000,
                'specs'    => [
                    'socket_type' => 'LGA1700',
                ],
            ],
            [
                'category' => 'CPU Cooler',
                'brand'    => 'be quiet!',
                'name'     => 'be quiet! Dark Rock Pro 5',
                'letak'    => 'Rak G1',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1800000,
                'cost'     => 1600000,
                'specs'    => [
                    'socket_type' => 'LGA1700',
                ],
            ],
            [
                'category' => 'CPU Cooler',
                'brand'    => 'Arctic',
                'name'     => 'Arctic Liquid Freezer III 360 AIO',
                'letak'    => 'Rak G2',
                'supplier' => $sup1,
                'pemodal'  => $owner,
                'price'    => 1650000,
                'cost'     => 1480000,
                'specs'    => [
                    'socket_type' => 'AM5',
                ],
            ],
            [
                'category' => 'CPU Cooler',
                'brand'    => 'Noctua',
                'name'     => 'Noctua NH-D15 G2',
                'letak'    => 'Rak G2',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 2100000,
                'cost'     => 1900000,
                'specs'    => [
                    'socket_type' => 'AM5',
                ],
            ],
            [
                'category' => 'CPU Cooler',
                'brand'    => 'Cooler Master',
                'name'     => 'Cooler Master MasterLiquid 240L Core',
                'letak'    => 'Rak G3',
                'supplier' => $sup2,
                'pemodal'  => $staff,
                'price'    => 680000,
                'cost'     => 600000,
                'specs'    => [
                    'socket_type' => 'AM4',
                ],
            ],

        ];

        // =====================================================================
        // INSERT SEMUA PRODUK
        // =====================================================================

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->first();

            if (! $category) {
                $this->command->warn("Kategori '{$item['category']}' tidak ditemukan, skip.");
                continue;
            }

            // Skip kalau produk sudah ada (by name)
            if (Product::where('name', $item['name'])->exists()) {
                $this->command->line("Skip (sudah ada): {$item['name']}");
                continue;
            }

            $product = Product::create([
                'category_id'     => $category->id,
                'brand'           => $item['brand'],
                'name'            => $item['name'],
                'letak_barang'    => $item['letak'],
                'description'     => "{$item['brand']} {$item['name']}",
                'technical_specs' => $item['specs'],
            ]);

            // Simpan spec ke product_specifications
            foreach ($item['specs'] as $key => $value) {
                $product->specifications()->create([
                    'spec_key'   => $key,
                    'spec_value' => $value,
                ]);
            }

            // Attach supplier
            $product->suppliers()->attach($item['supplier']->id, [
                'condition'         => 'New',
                'stock'             => rand(5, 30),
                'harga_beli'        => $item['cost'],
                'harga_jual_manual' => $item['price'],
                'pemodal_user_id'   => $item['pemodal']->id,
                'entry_date'        => now(),
            ]);

            $this->command->info("✓ {$item['category']}: {$item['name']}");
        }

        $this->command->newLine();
        $this->command->info('ProductSeeder selesai! Total: ' . count($products) . ' produk.');
    }
}
