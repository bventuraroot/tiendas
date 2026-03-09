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
        Schema::table('purchase_details', function (Blueprint $table) {
            // Cambiar precisión de 2 a 4 decimales para campos monetarios
            $table->decimal('unit_price', 12, 4)->change();
            $table->decimal('subtotal', 12, 4)->change();
            $table->decimal('tax_amount', 12, 4)->change();
            $table->decimal('total_amount', 12, 4)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            // Cambiar precisión de campos monetarios en tabla purchases
            $table->decimal('exenta', 12, 4)->change();
            $table->decimal('gravada', 12, 4)->change();
            $table->decimal('iva', 12, 4)->change();
            $table->decimal('contrns', 12, 4)->change();
            $table->decimal('fovial', 12, 4)->change();
            $table->decimal('iretenido', 12, 4)->change();
            $table->decimal('otros', 12, 4)->change();
            $table->decimal('total', 12, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            // Revertir a 2 decimales
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('subtotal', 10, 2)->change();
            $table->decimal('tax_amount', 10, 2)->change();
            $table->decimal('total_amount', 10, 2)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            // Revertir a 2 decimales
            $table->decimal('exenta', 10, 2)->change();
            $table->decimal('gravada', 10, 2)->change();
            $table->decimal('iva', 10, 2)->change();
            $table->decimal('contrns', 10, 2)->change();
            $table->decimal('fovial', 10, 2)->change();
            $table->decimal('iretenido', 10, 2)->change();
            $table->decimal('otros', 10, 2)->change();
            $table->decimal('total', 10, 2)->change();
        });
    }
};
