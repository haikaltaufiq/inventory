<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('supplier_id')->constrained(); // Karena di UI lu pilih supplier per item
            $table->unsignedBigInteger('product_supplier_id')->nullable();
            $table->string('item_name')->nullable();
            $table->text('item_specification')->nullable();
            $table->integer('quantity');
            $table->decimal('price_at_transaction', 15, 2); // Harga pas kejadian biar ga berubah kalo master data berubah
            $table->boolean('is_conflict')->default(false); // Flag buat catat kalo user maksa part gak match
            $table->timestamps();

            $table->index('product_supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
