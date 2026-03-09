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
        // Verificar si la columna 'motivo' ya existe antes de agregarla
        if (!Schema::hasColumn('sales', 'motivo')) {
            Schema::table('sales', function (Blueprint $table) {
                // Intentar agregar después de 'doc_related' si existe, sino después de 'id'
                if (Schema::hasColumn('sales', 'doc_related')) {
                    $table->text('motivo')->nullable()->after('doc_related');
                } else {
                    $table->text('motivo')->nullable()->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Solo eliminar la columna si existe
        if (Schema::hasColumn('sales', 'motivo')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('motivo');
            });
        }
    }
};
