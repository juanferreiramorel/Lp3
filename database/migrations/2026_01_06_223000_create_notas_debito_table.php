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
        Schema::create('notas_debito', function (Blueprint $table) {
            $table->id('id_nota_debito');
            $table->integer('id_venta')->nullable();
            $table->integer('id_cliente');
            $table->integer('id_sucursal');
            $table->bigInteger('id_timbrado');
            $table->date('fecha_emision');
            $table->string('nro_nota', 20); // Similar format to factura_nro
            // Totales
            $table->decimal('total', 19, 2);
            $table->decimal('total_exento', 19, 2)->default(0);
            $table->decimal('total_gravado_5', 19, 2)->default(0);
            $table->decimal('total_gravado_10', 19, 2)->default(0);
            $table->decimal('total_iva_5', 19, 2)->default(0);
            $table->decimal('total_iva_10', 19, 2)->default(0);
            $table->decimal('total_iva', 19, 2)->default(0);
            
            $table->string('estado', 30)->default('PENDIENTE');
            $table->integer('user_id');
            $table->text('motivo')->nullable(); // Razón de la nota de débito

            $table->timestamps();

            // Foreign Keys
            // Assuming table names based on context (ventas, clientes, sucursales, timbrados, users)
            $table->foreign('id_venta')->references('id_venta')->on('ventas')->onDelete('set null');
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes');
            $table->foreign('id_sucursal')->references('id_sucursal')->on('sucursales');
            $table->foreign('id_timbrado')->references('id_timbrado')->on('timbrados');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('detalle_notas_debito', function (Blueprint $table) {
            $table->id('id_detalle_nota');
            $table->unsignedBigInteger('id_nota_debito');
            $table->integer('id_producto');
            $table->integer('cantidad');
            $table->decimal('precio', 19, 2); // Precio unitario (con IVA)
            $table->bigInteger('id_tax')->nullable();
            
            // Tax calculations stored for record
            $table->decimal('precio_sin_impuesto', 19, 2)->default(0);
            $table->decimal('monto_impuesto', 19, 2)->default(0);

            $table->foreign('id_nota_debito')->references('id_nota_debito')->on('notas_debito')->onDelete('cascade');
            $table->foreign('id_producto')->references('id_producto')->on('productos');
            $table->foreign('id_tax')->references('id_tax')->on('taxes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_notas_debito');
        Schema::dropIfExists('notas_debito');
    }
};
