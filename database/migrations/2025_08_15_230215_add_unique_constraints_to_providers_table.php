<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            // Agregar índices únicos para NCR y NIT
            // Solo se aplican cuando los valores no son null
            $table->unique('ncr', 'providers_ncr_unique');
            $table->unique('nit', 'providers_nit_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropUnique('providers_ncr_unique');
            $table->dropUnique('providers_nit_unique');
        });
    }
};
