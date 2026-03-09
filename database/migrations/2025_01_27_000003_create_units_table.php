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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code', 10)->unique()->comment('Código del catálogo CAT-014 del MH');
            $table->string('unit_name', 100)->comment('Nombre de la unidad de medida');
            $table->string('unit_type', 50)->nullable()->comment('Tipo: peso, volumen, longitud, area, conteo, etc.');
            $table->text('description')->nullable()->comment('Descripción de la unidad');
            $table->boolean('is_active')->default(true)->comment('Indica si la unidad está activa');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['unit_code', 'is_active']);
            $table->index('unit_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('units');
    }
};
