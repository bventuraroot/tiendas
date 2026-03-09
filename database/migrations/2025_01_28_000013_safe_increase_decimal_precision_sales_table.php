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
            UPDATE sales
            SET
                totalamount = CASE
                    WHEN totalamount > 99.99999999 THEN 99.99999999
                    WHEN totalamount < -99.99999999 THEN -99.99999999
                    ELSE totalamount
                END
            WHERE totalamount IS NOT NULL
        ');

        // Ahora cambiar el tipo de columna a decimal(10,8)
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('totalamount', 10, 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Revertir a la precisión original
            $table->decimal('totalamount', 5, 2)->nullable()->change();
        });
    }
};
