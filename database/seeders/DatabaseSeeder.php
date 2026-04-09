<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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

        $supplier1 = Supplier::create([
            'nama_supplier' => 'PT Sumber Teknologi',
            'alamat' => 'Jakarta',
        ]);

        $supplier2 = Supplier::create([
            'nama_supplier' => 'CV Mega Komputer',
            'alamat' => 'Surabaya',
        ]);

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
                    'Socket' => 'LGA1700',
                    'Cores' => '24 Cores',
                    'Threads' => '32 Threads',
                    'Base Clock' => '3.2 GHz',
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
                    'Chipset' => 'Z790',
                    'Form Factor' => 'ATX',
                    'Memory' => 'DDR5',
                    'PCIe' => 'Gen 5',
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
                    'Capacity' => '64GB (2x32GB)',
                    'Speed' => '6000MHz',
                    'Type' => 'DDR5',
                    'RGB' => 'Yes',
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
                    'VRAM' => '24GB GDDR6X',
                    'Architecture' => 'Ada Lovelace',
                    'Interface' => '384-bit',
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
                    'Capacity' => '2TB',
                    'Read Speed' => '7450 MB/s',
                    'Write Speed' => '6900 MB/s',
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
                    'Wattage' => '1200W',
                    'Efficiency' => '80+ Platinum',
                    'Modular' => 'Full',
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
                    'Type' => 'Mid Tower',
                    'Glass' => 'Dual Tempered',
                    'Color' => 'Black',
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
                'specs' => [
                    'Radiator' => '360mm',
                    'LCD Display' => 'Yes',
                    'Fans' => '3x 120mm RGB',
                ],
            ],
        ];

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->firstOrFail();

            $product = Product::create([
                'category_id' => $category->id,
                'brand' => $item['brand'],
                'name' => $item['name'],
                'letak_barang' => $item['letak_barang'],
                'description' => 'High-end component for PC Enthusiast',
                'warranty' => '3 Years',
            ]);

            foreach ($item['specs'] as $key => $value) {
                $product->specifications()->create([
                    'spec_key' => $key,
                    'spec_value' => $value,
                ]);
            }

            $product->suppliers()->attach($item['supplier_id'], [
                'condition' => 'New',
                'stock' => 15,
                'harga_beli' => $item['cost'],
                'harga_jual_manual' => $item['price'],
                'pemodal_user_id' => $item['pemodal_user_id'],
                'entry_date' => now(),
            ]);
        }
    }
}
