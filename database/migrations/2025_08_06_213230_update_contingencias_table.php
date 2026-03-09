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
        Schema::table('contingencias', function (Blueprint $table) {
            // Agregar nuevos campos si no existen
            if (!Schema::hasColumn('contingencias', 'nombre')) {
                $table->string('nombre')->after('id');
            }

            if (!Schema::hasColumn('contingencias', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('nombre');
            }

            if (!Schema::hasColumn('contingencias', 'motivo')) {
                $table->text('motivo')->after('descripcion');
            }

            if (!Schema::hasColumn('contingencias', 'fecha_inicio')) {
                $table->timestamp('fecha_inicio')->after('motivo');
            }

            if (!Schema::hasColumn('contingencias', 'fecha_fin')) {
                $table->timestamp('fecha_fin')->after('fecha_inicio');
            }

            if (!Schema::hasColumn('contingencias', 'tipo_contingencia')) {
                $table->string('tipo_contingencia')->default('tecnica')->after('fecha_fin');
            }

            if (!Schema::hasColumn('contingencias', 'documentos_afectados')) {
                $table->integer('documentos_afectados')->default(0)->after('tipo_contingencia');
            }

            if (!Schema::hasColumn('contingencias', 'resolucion_mh')) {
                $table->string('resolucion_mh')->nullable()->after('documentos_afectados');
            }

            if (!Schema::hasColumn('contingencias', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
            }

            if (!Schema::hasColumn('contingencias', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            // Cambiar nombre de columnas si es necesario
            if (Schema::hasColumn('contingencias', 'idEmpresa')) {
                $table->renameColumn('idEmpresa', 'empresa_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contingencias', function (Blueprint $table) {
            // Revertir cambios
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'nombre', 'descripcion', 'motivo', 'fecha_inicio', 'fecha_fin',
                'tipo_contingencia', 'documentos_afectados', 'resolucion_mh',
                'approved_by', 'approved_at'
            ]);

            if (Schema::hasColumn('contingencias', 'empresa_id')) {
                $table->renameColumn('empresa_id', 'idEmpresa');
            }
        });
    }
};
