<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE product_supplier
            MODIFY `condition` ENUM('New', 'Used', 'Refurbished') NOT NULL DEFAULT 'New'
        ");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            UPDATE product_supplier
            SET `condition` = 'Used'
            WHERE `condition` = 'Refurbished'
        ");

        DB::statement("
            ALTER TABLE product_supplier
            MODIFY `condition` ENUM('New', 'Used') NOT NULL DEFAULT 'New'
        ");
    }
};
