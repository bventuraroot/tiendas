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
        // Eliminar campos de IVA de la tabla quotations
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('taxes');
        });

        // Eliminar campos de IVA de la tabla quotation_details
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Restaurar campos de IVA en la tabla quotations
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('taxes', 10, 2)->default(0)->after('subtotal');
        });

        // Restaurar campos de IVA en la tabla quotation_details
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(13.00)->after('subtotal');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
        });
    }
};
