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
        Schema::create('cuentas_a_cobrar', function (Blueprint $table) {
            $table->increments('id_cta');
            $table->integer('id_cliente');
            $table->integer('id_venta');
            $table->date('vencimiento');
            $table->float('importe', null, 0);
            $table->string('estado', 20)->nullable()->default('PENDIENTE');
            $table->integer('nro_cuota')->nullable();
            $table->integer('importe_pagado')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_a_cobrar');
    }
};
