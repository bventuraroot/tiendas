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
        Schema::table('purchases', function (Blueprint $table) {
            // Verificar si los campos existen antes de agregarlos
            if (!Schema::hasColumn('purchases', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 4)->nullable()->default(0)->after('total');
            }
            if (!Schema::hasColumn('purchases', 'payment_status')) {
                $table->integer('payment_status')->nullable()->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('purchases', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('purchases', 'credit_days')) {
                $table->integer('credit_days')->nullable()->after('payment_type');
            }
            if (!Schema::hasColumn('purchases', 'payment_due_date')) {
                $table->date('payment_due_date')->nullable()->after('credit_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'paid_amount',
                'payment_status',
                'payment_type',
                'credit_days',
                'payment_due_date'
            ]);
        });
    }
};
