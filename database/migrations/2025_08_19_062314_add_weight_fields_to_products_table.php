<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Campos de peso y medidas físicas
            $table->decimal('weight_grams', 10, 4)->nullable()->comment('Peso en gramos por unidad');
            $table->decimal('weight_lbs', 10, 4)->nullable()->comment('Peso en libras por unidad');
            $table->decimal('weight_kg', 10, 4)->nullable()->comment('Peso en kilogramos por unidad');

            // Campos de volumen
            $table->decimal('volume_ml', 10, 4)->nullable()->comment('Volumen en mililitros por unidad');
            $table->decimal('volume_liters', 10, 4)->nullable()->comment('Volumen en litros por unidad');

            // Campos de dimensiones
            $table->decimal('length_cm', 10, 4)->nullable()->comment('Longitud en centímetros');
            $table->decimal('width_cm', 10, 4)->nullable()->comment('Ancho en centímetros');
            $table->decimal('height_cm', 10, 4)->nullable()->comment('Alto en centímetros');

            // Campo para especificar la unidad base de medida
            $table->string('base_measure_unit', 10)->nullable()->comment('Unidad base de medida (gr, lb, kg, ml, l, cm)');

            // Campo para especificar el contenido por unidad (ej: "55 libras por saco")
            $table->string('content_per_unit', 100)->nullable()->comment('Descripción del contenido por unidad');

            // Campo para especificar el precio por unidad de medida base
            $table->decimal('price_per_base_unit', 10, 4)->nullable()->comment('Precio por unidad de medida base');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'weight_grams',
                'weight_lbs',
                'weight_kg',
                'volume_ml',
                'volume_liters',
                'length_cm',
                'width_cm',
                'height_cm',
                'base_measure_unit',
                'content_per_unit',
                'price_per_base_unit'
            ]);
        });
    }
};
