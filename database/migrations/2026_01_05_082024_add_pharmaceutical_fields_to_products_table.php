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
            // Número de registro sanitario
            $table->string('registration_number', 100)->nullable()->after('specialty')->comment('Número de registro sanitario del medicamento');
            
            // Fórmula del medicamento
            $table->text('formula')->nullable()->after('registration_number')->comment('Fórmula del medicamento (ingredientes activos)');
            
            // Unidad de medida (ej: mg, ml, unidades)
            $table->string('unit_measure', 50)->nullable()->after('formula')->comment('Unidad de medida (mg, ml, unidades, etc.)');
            
            // Forma de venta (ej: Venta libre, Con receta, Controlado)
            $table->string('sale_form', 50)->nullable()->after('unit_measure')->comment('Forma de venta (Venta libre, Con receta, Controlado)');
            
            // Tipo de producto (ej: Polifármico, Monofármico)
            $table->string('product_type', 50)->nullable()->after('sale_form')->comment('Tipo de producto (Polifármico, Monofármico, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'registration_number',
                'formula',
                'unit_measure',
                'sale_form',
                'product_type'
            ]);
        });
    }
};
