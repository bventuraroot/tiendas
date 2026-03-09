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
        Schema::table('contingencias', function (Blueprint $table) {
            // Agregar empresa_id si no existe
            if (!Schema::hasColumn('contingencias', 'empresa_id')) {
                // Verificar si existe idEmpresa para usarlo como referencia
                if (Schema::hasColumn('contingencias', 'idEmpresa')) {
                    // Agregar empresa_id como foreignId después de idEmpresa
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('idEmpresa');
                } else {
                    // Si no existe idEmpresa, agregar empresa_id después del id
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                }

                // Agregar la foreign key constraint
                $table->foreign('empresa_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contingencias', function (Blueprint $table) {
            // Eliminar la foreign key primero
            if (Schema::hasColumn('contingencias', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }
};
