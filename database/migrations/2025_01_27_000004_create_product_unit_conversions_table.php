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
        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->decimal('conversion_factor', 10, 4)->default(1.0000)->comment('Factor de conversión a unidad base');
            $table->decimal('price_multiplier', 10, 4)->default(1.0000)->comment('Multiplicador de precio');
            $table->boolean('is_default')->default(false)->comment('Indica si es la unidad por defecto');
            $table->boolean('is_active')->default(true)->comment('Indica si la conversión está activa');
            $table->text('notes')->nullable()->comment('Notas adicionales sobre la conversión');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['product_id', 'unit_id']);
            $table->index(['product_id', 'is_default']);
            $table->index(['product_id', 'is_active']);

            // Índice único para evitar duplicados de conversión por producto-unidad
            $table->unique(['product_id', 'unit_id'], 'product_unit_conversion_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_unit_conversions');
    }
};
