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
            // Eliminar campos redundantes
            $table->dropColumn([
                'weight_grams',
                'weight_kg',
                'volume_ml',
                'length_cm',
                'width_cm',
                'height_cm',
                'price_per_base_unit'
            ]);

            // Renombrar campos para mayor claridad
            $table->renameColumn('weight_lbs', 'weight_per_unit');
            $table->renameColumn('volume_liters', 'volume_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restaurar campos eliminados
            $table->decimal('weight_grams', 10, 4)->nullable();
            $table->decimal('weight_kg', 10, 4)->nullable();
            $table->decimal('volume_ml', 10, 4)->nullable();
            $table->decimal('length_cm', 10, 4)->nullable();
            $table->decimal('width_cm', 10, 4)->nullable();
            $table->decimal('height_cm', 10, 4)->nullable();
            $table->decimal('price_per_base_unit', 10, 4)->nullable();

            // Restaurar nombres originales
            $table->renameColumn('weight_per_unit', 'weight_lbs');
            $table->renameColumn('volume_per_unit', 'volume_liters');
        });
    }
};
