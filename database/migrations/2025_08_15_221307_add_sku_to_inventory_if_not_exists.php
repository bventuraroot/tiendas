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
        Schema::table('inventory', function (Blueprint $table) {
            // Agregar campos que podrían estar faltando y hacerlos nullable para evitar errores
            if (!Schema::hasColumn('inventory', 'sku')) {
                $table->string('sku')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('inventory', 'name')) {
                $table->string('name')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('inventory', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('inventory', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('description');
            }
            if (!Schema::hasColumn('inventory', 'category')) {
                $table->string('category')->nullable()->after('price');
            }
            if (!Schema::hasColumn('inventory', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('category');
            }
            if (!Schema::hasColumn('inventory', 'provider_id')) {
                $table->foreignId('provider_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('inventory', 'active')) {
                $table->boolean('active')->default(true)->after('provider_id');
            }
        });

        // Si existen campos que no son nullable, intentar usar SQL directo para hacerlos nullable
        try {
            \DB::statement('ALTER TABLE inventory MODIFY COLUMN sku VARCHAR(255) NULL');
        } catch (\Exception $e) {
            // Si falla, no es crítico, el campo ya debe existir
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory', function (Blueprint $table) {
            // Solo eliminar campos si existen
            $columnsToRemove = ['active', 'provider_id', 'user_id', 'category', 'price', 'description', 'name', 'sku'];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('inventory', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
