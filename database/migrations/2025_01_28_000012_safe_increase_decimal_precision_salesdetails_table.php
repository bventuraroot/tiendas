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
        // Primero, limpiar y ajustar los datos existentes para que sean compatibles con decimal(10,8)
        DB::statement('
            UPDATE salesdetails
            SET
                pricesale = CASE
                    WHEN pricesale > 99.99999999 THEN 99.99999999
                    WHEN pricesale < -99.99999999 THEN -99.99999999
                    ELSE pricesale
                END,
                priceunit = CASE
                    WHEN priceunit > 99.99999999 THEN 99.99999999
                    WHEN priceunit < -99.99999999 THEN -99.99999999
                    ELSE priceunit
                END,
                nosujeta = CASE
                    WHEN nosujeta > 99.99999999 THEN 99.99999999
                    WHEN nosujeta < -99.99999999 THEN -99.99999999
                    ELSE nosujeta
                END,
                exempt = CASE
                    WHEN exempt > 99.99999999 THEN 99.99999999
                    WHEN exempt < -99.99999999 THEN -99.99999999
                    ELSE exempt
                END,
                detained = CASE
                    WHEN detained IS NOT NULL THEN CASE
                        WHEN detained > 99.99999999 THEN 99.99999999
                        WHEN detained < -99.99999999 THEN -99.99999999
                        ELSE detained
                    END
                    ELSE NULL
                END,
                detained13 = CASE
                    WHEN detained13 > 99.99999999 THEN 99.99999999
                    WHEN detained13 < -99.99999999 THEN -99.99999999
                    ELSE detained13
                END,
                renta = CASE
                    WHEN renta > 99.99999999 THEN 99.99999999
                    WHEN renta < -99.99999999 THEN -99.99999999
                    ELSE renta
                END,
                fee = CASE
                    WHEN fee > 99.99999999 THEN 99.99999999
                    WHEN fee < -99.99999999 THEN -99.99999999
                    ELSE fee
                END,
                feeiva = CASE
                    WHEN feeiva > 99.99999999 THEN 99.99999999
                    WHEN feeiva < -99.99999999 THEN -99.99999999
                    ELSE feeiva
                END
        ');

        // Ahora cambiar el tipo de columna a decimal(10,8)
        Schema::table('salesdetails', function (Blueprint $table) {
            $table->decimal('pricesale', 10, 8)->change();
            $table->decimal('priceunit', 10, 8)->change();
            $table->decimal('nosujeta', 10, 8)->change();
            $table->decimal('exempt', 10, 8)->change();
            $table->decimal('detained', 10, 8)->nullable()->change();
            $table->decimal('detained13', 10, 8)->change();
            $table->decimal('renta', 10, 8)->change();
            $table->decimal('fee', 10, 8)->change();
            $table->decimal('feeiva', 10, 8)->change();
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
            // Revertir a la precisión original
            $table->decimal('pricesale', 5, 2)->change();
            $table->decimal('priceunit', 5, 2)->change();
            $table->decimal('nosujeta', 5, 2)->change();
            $table->decimal('exempt', 5, 2)->change();
            $table->decimal('detained', 5, 2)->nullable()->change();
            $table->decimal('detained13', 5, 2)->change();
            $table->decimal('renta', 5, 2)->change();
            $table->decimal('fee', 5, 2)->change();
            $table->decimal('feeiva', 5, 2)->change();
        });
    }
};
