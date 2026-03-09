<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla product_categories en este mismo archivo (sin crear más archivos).
     */
    public function up(): void
    {
        if (!Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->unique();
                $table->string('description', 500)->nullable();
                $table->timestamps();
            });
        }

        // Poblar con categorías que ya existen en products (no afecta datos existentes)
        if (Schema::hasTable('product_categories') && Schema::hasColumn('products', 'category')) {
            $existing = DB::table('products')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category');
            $now = now();
            foreach ($existing as $name) {
                DB::table('product_categories')->insertOrIgnore([
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Categorías por defecto (solo se insertan si no existen por el unique)
        if (Schema::hasTable('product_categories')) {
            $defaults = [
                'Analgésicos y Antiinflamatorios', 'Antibióticos', 'Antivirales', 'Antimicóticos',
                'Antiparasitarios', 'Antihistamínicos y Antialérgicos', 'Antigripales',
                'Antitusivos y Expectorantes', 'Antihipertensivos', 'Antiarrítmicos', 'Anticoagulantes',
                'Hipolipemiantes', 'Antiácidos y Gastroprotectores', 'Antidiarreicos', 'Laxantes',
                'Antiespasmodicos', 'Hepatoprotectores', 'Ansiolíticos', 'Antidepresivos',
                'Anticonvulsivantes', 'Sedantes e Hipnóticos', 'Antidiabéticos', 'Hormonas Tiroideas',
                'Corticosteroides', 'Hormonas Sexuales', 'Broncodilatadores', 'Antiasmáticos',
                'Descongestionantes', 'Cremas y Ungüentos', 'Antisépticos', 'Antimicóticos Tópicos',
                'Gotas Oftálmicas', 'Gotas Óticas', 'Vitaminas', 'Minerales', 'Suplementos Nutricionales',
                'Anticonceptivos', 'Vacunas', 'Material de Curación', 'Equipo Médico',
                'Productos de Higiene', 'Productos Naturales', 'Antipireticos',
            ];
            $now = now();
            foreach ($defaults as $name) {
                DB::table('product_categories')->insertOrIgnore([
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
