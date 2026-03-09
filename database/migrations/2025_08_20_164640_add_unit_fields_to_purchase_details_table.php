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
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->string('unit_code')->nullable()->after('unit_price');
            $table->unsignedBigInteger('unit_id')->nullable()->after('unit_code');
            $table->decimal('conversion_factor', 10, 4)->default(1.0000)->after('unit_id');

            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_code', 'unit_id', 'conversion_factor']);
        });
    }
};
