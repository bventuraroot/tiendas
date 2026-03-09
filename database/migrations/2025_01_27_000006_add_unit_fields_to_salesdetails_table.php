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
        Schema::table('salesdetails', function (Blueprint $table) {
            // Agregar campos para manejo de unidades de venta
            $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->onDelete('set null');
            $table->string('unit_name', 100)->nullable()->after('unit_id')->comment('Nombre de la unidad para referencia');
            $table->decimal('conversion_factor', 10, 4)->default(1.0000)->after('unit_name')->comment('Factor de conversión usado');
            $table->decimal('base_quantity_used', 15, 4)->default(0.0000)->after('conversion_factor')->comment('Cantidad descontada del inventario en unidad base');
            
            // Índices para optimizar consultas
            $table->index(['product_id', 'unit_id']);
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
            $table->dropForeign(['unit_id']);
            $table->dropIndex(['product_id', 'unit_id']);
            $table->dropColumn(['unit_id', 'unit_name', 'conversion_factor', 'base_quantity_used']);
        });
    }
};
