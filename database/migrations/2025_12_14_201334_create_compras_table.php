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
        Schema::create('compras', function (Blueprint $table) {
            $table->increments('id_compra');
            $table->integer('id_proveedor');
            $table->date('fecha_compra');
            $table->decimal('total', 19);
            $table->integer('user_id');
            $table->integer('id_sucursal')->nullable();
            $table->string('condicion_compra', 20)->default('CONTADO');
            $table->integer('intervalo')->nullable();
            $table->string('factura', 20);
            $table->integer('cantidad_cuotas')->nullable();
            $table->string('estado', 20)->default('COMPLETADO');
            $table->integer('id_orden_compra')->nullable();
            $table->decimal('total_exento', 19)->default(0);
            $table->decimal('total_gravado_5', 19)->default(0);
            $table->decimal('total_gravado_10', 19)->default(0);
            $table->decimal('total_iva_5', 19)->default(0);
            $table->decimal('total_iva_10', 19)->default(0);
            $table->decimal('total_iva', 19)->default(0);

            $table->unique(['id_proveedor', 'factura'], 'compras_proveedor_factura_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
