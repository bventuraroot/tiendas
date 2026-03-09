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
            $table->unsignedBigInteger('dte_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('tipo_error')->comment('Tipo de error: hacienda, firma, conexion, etc.');
            $table->string('codigo_error')->comment('Código específico del error');
            $table->text('descripcion')->comment('Descripción detallada del error');
            $table->json('datos_adicionales')->nullable()->comment('Datos adicionales del error');
            $table->timestamps();

            // Índices
            $table->index(['dte_id']);
            $table->index(['sale_id']);
            $table->index(['company_id']);
            $table->index(['tipo_error']);
            $table->index(['created_at']);

            // Foreign keys
            $table->foreign('dte_id')->references('id')->on('dtes')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
