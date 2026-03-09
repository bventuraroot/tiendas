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
            $table->string('unidad_medida', 50)->nullable()->after('valores_referencia_especificos')->comment('Unidad de medida del resultado (ej: mUI/mL, ng/mL, mg/dL, etc.)');
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
            $table->dropColumn('unidad_medida');
        });
    }
};
