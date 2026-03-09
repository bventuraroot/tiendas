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
        Schema::table('product_prices', function (Blueprint $table) {
            // Cambiar la precisión decimal de 2 a 4 decimales
            $table->decimal('price', 12, 4)->change()->comment('Precio para esta unidad específica');
            $table->decimal('cost_price', 12, 4)->nullable()->change()->comment('Precio de costo para esta unidad');
            $table->decimal('wholesale_price', 12, 4)->nullable()->change()->comment('Precio al por mayor');
            $table->decimal('retail_price', 12, 4)->nullable()->change()->comment('Precio al detalle');
            $table->decimal('special_price', 12, 4)->nullable()->change()->comment('Precio especial/promocional');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_prices', function (Blueprint $table) {
            // Revertir a 2 decimales
            $table->decimal('price', 10, 2)->change()->comment('Precio para esta unidad específica');
            $table->decimal('cost_price', 10, 2)->nullable()->change()->comment('Precio de costo para esta unidad');
            $table->decimal('wholesale_price', 10, 2)->nullable()->change()->comment('Precio al por mayor');
            $table->decimal('retail_price', 10, 2)->nullable()->change()->comment('Precio al detalle');
            $table->decimal('special_price', 10, 2)->nullable()->change()->comment('Precio especial/promocional');
        });
    }
};
