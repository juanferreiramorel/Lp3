<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->comment('Tabla de pagos realizados a proveedores por compras');
            $table->increments('id_pago')->comment('ID único del pago');
            $table->integer('id_compra')->comment('ID de la compra asociada');
            $table->integer('user_id')->comment('Usuario que registró el pago');
            $table->integer('id_metodo_pago')->comment('Método de pago utilizado');
            $table->date('pago_fecha')->comment('Fecha en que se realizó el pago');
            $table->decimal('pago_importe', 19)->comment('Importe del pago');
            $table->string('pago_estado', 20)->default('PAGADO')->comment('Estado del pago (PAGADO)');
            $table->string('nro_voucher', 50)->nullable()->comment('Número de voucher o comprobante de pago');
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
            $table->timestamp('updated_at')->nullable()->default(DB::raw("now()"));
            $table->integer('id_useranulado')->nullable();
            $table->timestamp('fechahora_anulado')->nullable();
            $table->integer('id_cta')->nullable()->index('idx_pagos_id_cta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
