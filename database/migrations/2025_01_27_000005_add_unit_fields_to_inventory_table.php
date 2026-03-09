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
        Schema::table('inventory', function (Blueprint $table) {
            // Agregar campos para manejo de unidades
            $table->foreignId('base_unit_id')->nullable()->after('quantity')->constrained('units')->onDelete('set null');
            $table->decimal('base_quantity', 15, 4)->default(0.0000)->after('base_unit_id')->comment('Cantidad en unidad base');
            $table->decimal('base_unit_price', 10, 2)->default(0.00)->after('base_quantity')->comment('Precio por unidad base');
            
            // Índices para optimizar consultas
            $table->index(['product_id', 'base_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
            $table->dropIndex(['product_id', 'base_unit_id']);
            $table->dropColumn(['base_unit_id', 'base_quantity', 'base_unit_price']);
        });
    }
};
