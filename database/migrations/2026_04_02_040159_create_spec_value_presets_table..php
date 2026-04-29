<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spec_value_presets', function (Blueprint $table) {
            $table->id();
            $table->string('spec_key', 100)->index();
            $table->string('spec_value', 255);
            $table->timestamps();
            $table->unique(['spec_key', 'spec_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spec_value_presets');
    }
};
