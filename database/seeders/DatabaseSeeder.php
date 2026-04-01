<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. USERS (Gas awal buat login)
        User::create([
            'name' => 'Staff',
            'email' => 'staff@gmail.com',
            'password' => Hash::make('123'),
            'role' => 'staff'
        ]);

        User::create([
            'name' => 'Owner User',
            'email' => 'owner@gmail.com',
            'password' => Hash::make('123'),
            'role' => 'owner'
        ]);

        // 2. CATEGORIES (Lengkap buat ekosistem perakitan PC)
        $catProcessor   = Category::create(['name' => 'Processor']);
        $catMotherboard = Category::create(['name' => 'Motherboard']);
        $catRam         = Category::create(['name' => 'RAM']);
        $catVga         = Category::create(['name' => 'VGA Card']);
        $catStorage     = Category::create(['name' => 'Storage (SSD/HDD)']);
        $catPsu         = Category::create(['name' => 'Power Supply']);
        $catCasing      = Category::create(['name' => 'Casing']);
        $catCooler      = Category::create(['name' => 'Cooling System']);

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

        // 3. SUPPLIERS
        $supplier1 = Supplier::create([
            'nama_supplier' => 'PT Sumber Teknologi',
            'alamat' => 'Jakarta'
        ]);

        $supplier2 = Supplier::create([
            'nama_supplier' => 'CV Mega Komputer',
            'alamat' => 'Surabaya'
        ]);

        // 6. CUSTOMERS
        Customer::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@email.com',
            'phone' => '081234567890',
            'address' => 'Jakarta Selatan'
        ]);

        Customer::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@email.com',
            'phone' => '081234567891',
            'address' => 'Bandung'
        ]);

        // Product

        $supplierId = Supplier::first()->id;

        $products = [
            [
                'category' => 'Processor',
                'name' => 'Intel Core i9-14900K',
                'price' => 9500000,
                'cost' => 8800000,
                'specs' => [
                    'Socket' => 'LGA1700',
                    'Cores' => '24 Cores',
                    'Threads' => '32 Threads',
                    'Base Clock' => '3.2 GHz'
                ]
            ],
            [
                'category' => 'Motherboard',
                'name' => 'ASUS ROG MAXIMUS Z790 HERO',
                'price' => 11000000,
                'cost' => 10200000,
                'specs' => [
                    'Chipset' => 'Z790',
                    'Form Factor' => 'ATX',
                    'Memory' => 'DDR5',
                    'PCIe' => 'Gen 5'
                ]
            ],
            [
                'category' => 'RAM',
                'name' => 'Corsair Dominator Titanium 64GB DDR5 6000MHz',
                'price' => 4500000,
                'cost' => 4100000,
                'specs' => [
                    'Capacity' => '64GB (2x32GB)',
                    'Speed' => '6000MHz',
                    'Type' => 'DDR5',
                    'RGB' => 'Yes'
                ]
            ],
            [
                'category' => 'VGA Card',
                'name' => 'NVIDIA RTX 4090 Founders Edition',
                'price' => 32000000,
                'cost' => 29500000,
                'specs' => [
                    'VRAM' => '24GB GDDR6X',
                    'Architecture' => 'Ada Lovelace',
                    'Interface' => '384-bit'
                ]
            ],
            [
                'category' => 'Storage (SSD/HDD)',
                'name' => 'Samsung 990 Pro 2TB NVMe Gen4',
                'price' => 2800000,
                'cost' => 2500000,
                'specs' => [
                    'Capacity' => '2TB',
                    'Read Speed' => '7450 MB/s',
                    'Write Speed' => '6900 MB/s'
                ]
            ],
            [
                'category' => 'Power Supply',
                'name' => 'ROG Thor 1200W Platinum II',
                'price' => 5500000,
                'cost' => 5100000,
                'specs' => [
                    'Wattage' => '1200W',
                    'Efficiency' => '80+ Platinum',
                    'Modular' => 'Full'
                ]
            ],
            [
                'category' => 'Casing',
                'name' => 'LIAN LI O11 Dynamic EVO',
                'price' => 2700000,
                'cost' => 2400000,
                'specs' => [
                    'Type' => 'Mid Tower',
                    'Glass' => 'Dual Tempered',
                    'Color' => 'Black'
                ]
            ],
            [
                'category' => 'Cooling System',
                'name' => 'NZXT Kraken Elite 360 RGB',
                'price' => 4200000,
                'cost' => 3800000,
                'specs' => [
                    'Radiator' => '360mm',
                    'LCD Display' => 'Yes',
                    'Fans' => '3x 120mm RGB'
                ]
            ],
        ];

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->first();

            $product = Product::create([
                'category_id' => $category->id,
                'name' => $item['name'],
                'selling_price' => $item['price'],
                'description' => 'High-end component for PC Enthusiast',
                'warranty' => '3 Years'
            ]);

            // Insert ke table specifications
            foreach ($item['specs'] as $key => $value) {
                $product->specifications()->create([
                    'spec_key' => $key,
                    'spec_value' => $value
                ]);
            }

            // Insert ke pivot product_supplier (Stock Management)
            $product->suppliers()->attach($supplierId, [
                'condition' => 'New',
                'stock' => 15,
                'harga_beli' => $item['cost'],
                'harga_jual_manual' => $item['price'],
                'entry_date' => now(),
            ]);
        }
    }
}
