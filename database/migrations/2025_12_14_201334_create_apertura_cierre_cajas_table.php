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
        Schema::create('apertura_cierre_cajas', function (Blueprint $table) {
            $table->increments('id_apertura');
            $table->integer('id_caja');
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->decimal('monto_apertura', 19);
            $table->decimal('monto_cierre', 19)->nullable();
            $table->integer('user_id');
            $table->string('estado', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apertura_cierre_cajas');
    }
};
