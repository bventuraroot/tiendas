<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla de Laboratorios Farmacéuticos
     */
    public function up()
    {
        Schema::create('pharmaceutical_laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->string('code', 50)->nullable()->unique();
            $table->string('country', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('website', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Agregar campo a products si no existe
        if (!Schema::hasColumn('products', 'pharmaceutical_laboratory_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('pharmaceutical_laboratory_id')
                    ->nullable()
                    ->after('marca_id')
                    ->constrained('pharmaceutical_laboratories')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('products', 'pharmaceutical_laboratory_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['pharmaceutical_laboratory_id']);
                $table->dropColumn('pharmaceutical_laboratory_id');
            });
        }
        
        Schema::dropIfExists('pharmaceutical_laboratories');
    }
};


