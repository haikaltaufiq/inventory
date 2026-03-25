<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supplier;
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
    }
}
