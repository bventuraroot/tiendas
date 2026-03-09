<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega categorías por defecto para tienda/supermercado (sin afectar existentes).
     */
    public function up(): void
    {
        if (!Schema::hasTable('product_categories')) {
            return;
        }

        $defaults = [
            'Abarrotes',
            'Lácteos',
            'Bebidas',
            'Carnes y Embutidos',
            'Panadería y Repostería',
            'Frutas y Verduras',
            'Congelados',
            'Enlatados y Conservas',
            'Limpieza del Hogar',
            'Cuidado Personal',
            'Papelería',
            'Otros',
        ];

        $now = now();
        foreach ($defaults as $name) {
            DB::table('product_categories')->insertOrIgnore([
                'name' => $name,
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se eliminan categorías para evitar pérdida de datos personalizados
    }
};
