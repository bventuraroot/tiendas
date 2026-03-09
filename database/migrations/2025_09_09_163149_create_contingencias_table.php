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
        Schema::create('contingencias', function (Blueprint $table) {
            $table->id();

            // Campos principales
            $table->string('nombre');
            $table->foreignId('empresa_id')->constrained('companies')->onDelete('cascade');
            $table->string('tipo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->text('motivo');
            $table->enum('estado', ['pendiente', 'activa', 'finalizada', 'cancelada'])->default('pendiente');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            // Campos legacy para compatibilidad con sistema existente
            $table->string('idEmpresa')->nullable();
            $table->string('idTienda')->nullable();
            $table->string('codInterno')->nullable();
            $table->integer('versionJson')->nullable();
            $table->string('ambiente')->nullable();
            $table->string('codEstado')->nullable();
            $table->string('codigoGeneracion')->nullable();
            $table->date('fechaCreacion')->nullable();
            $table->time('horaCreacion')->nullable();
            $table->date('fInicio')->nullable();
            $table->date('fFin')->nullable();
            $table->time('hInicio')->nullable();
            $table->time('hFin')->nullable();
            $table->integer('tipoContingencia')->nullable();
            $table->text('motivoContingencia')->nullable();
            $table->string('nombreResponsable')->nullable();
            $table->string('tipoDocResponsable')->nullable();
            $table->string('nuDocResponsable')->nullable();
            $table->string('selloRecibido')->nullable();
            $table->dateTime('fhRecibido')->nullable();
            $table->string('codEstadoHacienda')->nullable();
            $table->string('estadoHacienda')->nullable();
            $table->string('codigoMsg')->nullable();
            $table->string('clasificaMsg')->nullable();
            $table->text('descripcionMsg')->nullable();
            $table->text('observacionesMsg')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['empresa_id', 'estado']);
            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contingencias');
    }
};
