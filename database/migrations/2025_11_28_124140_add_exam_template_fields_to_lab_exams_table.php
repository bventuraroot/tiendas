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
        Schema::table('lab_exams', function (Blueprint $table) {
            $table->string('template_id', 100)->nullable()->after('valores_referencia')->comment('ID de la plantilla de formato del examen');
            $table->json('valores_referencia_especificos')->nullable()->after('template_id')->comment('Valores de referencia específicos según el formato del examen');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lab_exams', function (Blueprint $table) {
            $table->dropColumn(['template_id', 'valores_referencia_especificos']);
        });
    }
};
