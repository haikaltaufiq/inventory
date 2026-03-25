<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');

            $table->enum('condition', ['New', 'Used'])->default('New');
            $table->integer('stock')->default(0);
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('harga_jual_manual', 15, 2)->nullable(); // Overide harga buat barang bekas

            $table->string('warranty_detail')->nullable();
            $table->text('note')->nullable();
            $table->date('entry_date');
            $table->timestamps();

            // Biar gak double input untuk kondisi yang sama dari supplier yang sama
            $table->unique(['product_id', 'supplier_id', 'condition'], 'prod_supp_cond_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
