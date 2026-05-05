<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('sales_name');
            $table->string('transaction_mode', 30)->default('sparepart');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('service_fee', 15, 2)->default(0);
            $table->decimal('installation_fee', 15, 2)->default(0);
            $table->decimal('service_labor_fee', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('marketing_fee', 15, 2)->default(0);
            $table->decimal('final_total', 15, 2);
            $table->enum('type', ['Invoice', 'Quotation', 'DO']);
            $table->enum('status', ['Pending', 'Completed', 'Cancelled'])->default('Pending');
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
