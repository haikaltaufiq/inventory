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
            $table->foreignId('pemodal_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('condition', ['New', 'Used', 'Refurbished'])->default('New');
            $table->integer('stock')->default(0);
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('harga_jual_manual', 15, 2)->nullable();

            $table->string('warranty_detail')->nullable();
            $table->text('note')->nullable();
            $table->date('entry_date');
            $table->timestamps();

            $table->index('product_id', 'product_supplier_product_id_idx');
            $table->index('supplier_id', 'product_supplier_supplier_id_idx');
            $table->unique(
                ['product_id', 'supplier_id', 'condition', 'pemodal_user_id'],
                'prod_supp_cond_pemodal_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
