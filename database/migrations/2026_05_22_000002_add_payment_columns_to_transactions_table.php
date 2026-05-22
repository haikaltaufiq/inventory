<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'snap_token')) {
                $table->string('snap_token')->nullable()->after('description');
            }

            if (! Schema::hasColumn('transactions', 'payment_url')) {
                $table->text('payment_url')->nullable()->after('snap_token');
            }

            if (! Schema::hasColumn('transactions', 'payment_status')) {
                $table->string('payment_status', 30)->default('pending')->after('payment_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'payment_status')) {
                $table->dropColumn('payment_status');
            }

            if (Schema::hasColumn('transactions', 'payment_url')) {
                $table->dropColumn('payment_url');
            }

            if (Schema::hasColumn('transactions', 'snap_token')) {
                $table->dropColumn('snap_token');
            }
        });
    }
};
