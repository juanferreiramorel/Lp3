<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('notas_debito', function (Blueprint $table) {
            $table->integer('id_apertura')->nullable();
            $table->foreign('id_apertura')->references('id_apertura')->on('apertura_cierre_cajas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_debito', function (Blueprint $table) {
            $table->dropForeign(['id_apertura']);
            $table->dropColumn('id_apertura');
        });
    }
};
