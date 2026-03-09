<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usar SQL directo para evitar problemas con Doctrine DBAL
        DB::statement('ALTER TABLE `lab_results` MODIFY COLUMN `valor_referencia` TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a VARCHAR(200)
        DB::statement('ALTER TABLE `lab_results` MODIFY COLUMN `valor_referencia` VARCHAR(200) NULL');
    }
};
