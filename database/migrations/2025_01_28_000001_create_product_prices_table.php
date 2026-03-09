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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2)->comment('Precio para esta unidad específica');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Precio de costo para esta unidad');
            $table->decimal('wholesale_price', 10, 2)->nullable()->comment('Precio al por mayor');
            $table->decimal('retail_price', 10, 2)->nullable()->comment('Precio al detalle');
            $table->decimal('special_price', 10, 2)->nullable()->comment('Precio especial/promocional');
            $table->boolean('is_active')->default(true)->comment('Indica si este precio está activo');
            $table->boolean('is_default')->default(false)->comment('Indica si es el precio por defecto');
            $table->text('notes')->nullable()->comment('Notas adicionales sobre el precio');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['product_id', 'unit_id']);
            $table->index(['product_id', 'is_default']);
            $table->index(['product_id', 'is_active']);

            // Índice único para evitar duplicados de precio por producto-unidad
            $table->unique(['product_id', 'unit_id'], 'product_price_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
