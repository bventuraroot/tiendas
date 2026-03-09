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
        Schema::create('dte_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dte_id')->constrained('dte')->onDelete('cascade');
            $table->string('tipo_error'); // validacion, network, autenticacion, firma, hacienda, sistema, datos
            $table->string('codigo_error');
            $table->text('descripcion');
            $table->json('detalles')->nullable();
            $table->json('stack_trace')->nullable();
            $table->integer('intentos_realizados')->default(0);
            $table->integer('max_intentos')->default(3);
            $table->timestamp('proximo_reintento')->nullable();
            $table->boolean('resuelto')->default(false);
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resuelto_en')->nullable();
            $table->string('solucion_aplicada')->nullable(); // manual, automatico, contingencia
            $table->timestamps();

            // Índices
            $table->index(['dte_id', 'resuelto']);
            $table->index(['tipo_error', 'resuelto']);
            $table->index('proximo_reintento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_errors');
    }
};
