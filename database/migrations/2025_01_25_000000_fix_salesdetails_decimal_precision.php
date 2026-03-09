<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Cambiar la precisión de las columnas decimales para permitir valores más grandes
        Schema::table('salesdetails', function (Blueprint $table) {
            // Cambiar de decimal(10,8) a decimal(15,2) para permitir valores hasta 999,999,999,999.99
            $table->decimal('pricesale', 15, 2)->change();
            $table->decimal('priceunit', 15, 2)->change();
            $table->decimal('nosujeta', 15, 2)->change();
            $table->decimal('exempt', 15, 2)->change();
            $table->decimal('detained', 15, 2)->nullable()->change();
            $table->decimal('detained13', 15, 2)->change();
            $table->decimal('renta', 15, 2)->change();
            $table->decimal('fee', 15, 2)->change();
            $table->decimal('feeiva', 15, 2)->change();
            $table->decimal('reserva', 15, 2)->change();
            $table->decimal('ruta', 15, 2)->change();
            $table->decimal('destino', 15, 2)->change();
            $table->decimal('linea', 15, 2)->change();
            $table->decimal('canal', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salesdetails', function (Blueprint $table) {
            // Revertir a la precisión anterior
            $table->decimal('pricesale', 10, 8)->change();
            $table->decimal('priceunit', 10, 8)->change();
            $table->decimal('nosujeta', 10, 8)->change();
            $table->decimal('exempt', 10, 8)->change();
            $table->decimal('detained', 10, 8)->nullable()->change();
            $table->decimal('detained13', 10, 8)->change();
            $table->decimal('renta', 10, 8)->change();
            $table->decimal('fee', 10, 8)->change();
            $table->decimal('feeiva', 10, 8)->change();
            $table->decimal('reserva', 10, 8)->change();
            $table->decimal('ruta', 10, 8)->change();
            $table->decimal('destino', 10, 8)->change();
            $table->decimal('linea', 10, 8)->change();
            $table->decimal('canal', 10, 8)->change();
        });
    }
};

