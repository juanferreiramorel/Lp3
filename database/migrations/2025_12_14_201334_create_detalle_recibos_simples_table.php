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
        Schema::create('detalle_recibos_simples', function (Blueprint $table) {
            $table->comment('Detalle de conceptos/items de cada recibo');
            $table->increments('id_detalle_recibo');
            $table->integer('id_recibo')->index('idx_detalle_recibo');
            $table->string('concepto')->comment('Descripción del concepto o servicio');
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unitario', 19);
            $table->decimal('subtotal', 19);
            $table->integer('id_producto')->nullable()->index('idx_detalle_producto')->comment('Referencia al producto (opcional, si no se usa concepto manual)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_recibos_simples');
    }
};
