<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->nullable()->constrained('inventory')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('type', [
                'entrada_inicial',
                'compra',
                'ajuste_manual',
                'venta',
                'anulacion_compra',
                'anulacion_venta',
            ]);
            $table->decimal('quantity_before', 15, 4)->default(0);
            $table->decimal('quantity_change', 15, 4)->default(0); // positivo=entrada, negativo=salida
            $table->decimal('quantity_after', 15, 4)->default(0);
            $table->decimal('base_quantity_before', 15, 4)->default(0);
            $table->decimal('base_quantity_change', 15, 4)->default(0);
            $table->decimal('base_quantity_after', 15, 4)->default(0);
            $table->string('reference_type')->nullable(); // Purchase, Sale, Manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_doc')->nullable(); // Número de documento
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['inventory_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
