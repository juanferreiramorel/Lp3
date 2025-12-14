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
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id_venta');
            $table->integer('id_apertura')->nullable();
            $table->integer('id_cliente');
            $table->decimal('total', 19);
            $table->date('fecha_venta');
            $table->string('factura_nro', 20);
            $table->integer('user_id');
            $table->string('condicion_venta', 50)->nullable();
            $table->integer('intervalo')->nullable()->default(0)->comment('intervalo para saber cada cuendo sera el vencimiento solo para creditos');
            $table->integer('cantidad_cuota')->nullable()->default(0);
            $table->string('estado', 30)->nullable();
            $table->integer('id_sucursal')->nullable();
            $table->integer('id_pedido')->nullable();
            $table->bigInteger('id_timbrado')->nullable()->index();
            $table->decimal('total_exento', 19)->default(0);
            $table->decimal('total_gravado_5', 19)->default(0);
            $table->decimal('total_gravado_10', 19)->default(0);
            $table->decimal('total_iva_5', 19)->default(0);
            $table->decimal('total_iva_10', 19)->default(0);
            $table->decimal('total_iva', 19)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
