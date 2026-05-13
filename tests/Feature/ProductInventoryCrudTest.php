<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductInventoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_from_inventory_modal_payload(): void
    {
        $user = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
        $category = Category::query()->create(['name' => 'Uncategorized']);
        $supplier = Supplier::query()->create([
            'nama_supplier' => 'Supplier A',
            'alamat' => 'Jakarta',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('products.store'), [
                'name' => 'Modal Product',
                'brand' => 'Brand A',
                'category_id' => $category->id,
                'letak_barang' => 'Rak A',
                'description' => 'Created from modal',
                'suppliers' => [[
                    'mode' => 'existing',
                    'supplier_id' => $supplier->id,
                    'pemodal_user_id' => $user->id,
                    'stock' => 3,
                    'harga_beli' => 1000,
                    'harga_jual' => 1500,
                    'condition' => 'New',
                ]],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Produk berhasil ditambah.');

        $this->assertDatabaseHas('products', [
            'name' => 'Modal Product',
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('product_supplier', [
            'supplier_id' => $supplier->id,
            'stock' => 3,
            'harga_beli' => 1000,
            'harga_jual_manual' => 1500,
        ]);
    }

    public function test_product_can_be_renamed_without_losing_existing_supplier_payload(): void
    {
        $user = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
        $category = Category::query()->create(['name' => 'Uncategorized']);
        $supplier = Supplier::query()->create([
            'nama_supplier' => 'Supplier A',
            'alamat' => 'Jakarta',
        ]);
        $product = Product::query()->create([
            'name' => 'Old Name',
            'brand' => 'Brand A',
            'category_id' => $category->id,
        ]);

        $product->suppliers()->attach($supplier->id, [
            'pemodal_user_id' => $user->id,
            'condition' => 'New',
            'stock' => 5,
            'harga_beli' => 2000,
            'harga_jual_manual' => 3000,
            'entry_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->putJson(route('products.update', $product), [
                'name' => 'New Name',
                'brand' => 'Brand A',
                'category_id' => $category->id,
                'letak_barang' => '',
                'description' => '',
                'suppliers' => [[
                    'mode' => 'existing',
                    'supplier_id' => $supplier->id,
                    'pemodal_user_id' => $user->id,
                    'stock' => 5,
                    'harga_beli' => 2000,
                    'harga_jual' => 3000,
                    'condition' => 'New',
                ]],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Produk berhasil diupdate.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
        ]);
        $this->assertDatabaseHas('product_supplier', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'stock' => 5,
            'harga_beli' => 2000,
            'harga_jual_manual' => 3000,
        ]);
    }
}
