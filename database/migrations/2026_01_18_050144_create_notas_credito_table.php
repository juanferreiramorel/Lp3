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
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->id('id_nota_credito');
            $table->unsignedBigInteger('id_venta');
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedBigInteger('id_timbrado');
            $table->date('fecha_emision');
            $table->string('nro_nota', 20);
            $table->decimal('total', 19, 2);
            $table->decimal('total_exento', 19, 2)->default(0);
            $table->decimal('total_gravado_5', 19, 2)->default(0);
            $table->decimal('total_gravado_10', 19, 2)->default(0);
            $table->decimal('total_iva_5', 19, 2)->default(0);
            $table->decimal('total_iva_10', 19, 2)->default(0);
            $table->decimal('total_iva', 19, 2)->default(0);
            $table->string('estado', 30)->default('PENDIENTE'); // PENDIENTE, COMPLETADO, ANULADO
            $table->integer('user_id');
            $table->text('motivo')->nullable();
            
            // Allow tracking which cash register session it belongs to
            $table->unsignedBigInteger('id_apertura')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_venta')->references('id_venta')->on('ventas');
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes');
            $table->foreign('id_sucursal')->references('id_sucursal')->on('sucursales');
            $table->foreign('id_timbrado')->references('id_timbrado')->on('timbrados');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('detalle_notas_credito', function (Blueprint $table) {
            $table->bigIncrements('id_detalle_nota_credito');
            $table->unsignedBigInteger('id_nota_credito');
            $table->integer('id_producto');
            $table->integer('cantidad');
            $table->decimal('precio', 19, 2); // Precio Unitario Con IVA
            $table->bigInteger('id_tax')->nullable();
            
            // Breakdown for reporting
            $table->decimal('precio_sin_impuesto', 19, 2)->default(0);
            $table->decimal('monto_impuesto', 19, 2)->default(0);

            $table->foreign('id_nota_credito')->references('id_nota_credito')->on('notas_credito')->onDelete('cascade');
            $table->foreign('id_producto')->references('id_producto')->on('productos');
            $table->foreign('id_tax')->references('id_tax')->on('taxes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_notas_credito');
        Schema::dropIfExists('notas_credito');
    }
};
