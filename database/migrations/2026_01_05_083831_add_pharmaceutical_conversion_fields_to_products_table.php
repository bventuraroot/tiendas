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
        Schema::table('products', function (Blueprint $table) {
            // Campos para configurar conversiones farmacéuticas
            $table->integer('pastillas_per_blister')->nullable()->after('product_type')->comment('Número de pastillas por blister');
            $table->integer('blisters_per_caja')->nullable()->after('pastillas_per_blister')->comment('Número de blisters por caja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'pastillas_per_blister',
                'blisters_per_caja'
            ]);
        });
    }
};
