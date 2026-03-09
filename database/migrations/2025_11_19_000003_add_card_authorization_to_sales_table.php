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
        Schema::table('sales', function (Blueprint $table) {
            // Agregar columna para número de autorización de tarjeta
            $table->string('card_authorization_number', 100)->nullable()->after('waytopay')->comment('Número de autorización del voucher de tarjeta');
            
            // Agregar columna para tipo de tarjeta si es necesario
            $table->enum('card_type', ['visa', 'mastercard', 'american_express', 'dinners', 'otra'])->nullable()->after('card_authorization_number');
            
            // Agregar columna para los últimos 4 dígitos de la tarjeta
            $table->string('card_last_four', 4)->nullable()->after('card_type')->comment('Últimos 4 dígitos de la tarjeta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['card_authorization_number', 'card_type', 'card_last_four']);
        });
    }
};

