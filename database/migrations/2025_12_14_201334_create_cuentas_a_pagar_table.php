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
        Schema::create('cuentas_a_pagar', function (Blueprint $table) {
            $table->increments('id_cta');
            $table->integer('id_proveedor');
            $table->integer('id_compra');
            $table->date('vencimiento');
            $table->decimal('importe', 19);
            $table->integer('nro_cuota');
            $table->string('estado', 20)->nullable()->default('PENDIENTE');
            $table->decimal('importe_pagado', 19)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_a_pagar');
    }
};
