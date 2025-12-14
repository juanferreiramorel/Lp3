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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->increments('id_pedido');
            $table->integer('id_cliente');
            $table->date('fecha_pedido');
            $table->decimal('total_pedido', 10);
            $table->integer('id_sucursal')->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->integer('id_usuario')->nullable();
            $table->decimal('total_exento', 19)->default(0);
            $table->decimal('total_gravado_5', 19)->default(0);
            $table->decimal('total_gravado_10', 19)->default(0);
            $table->decimal('total_iva_5', 19)->default(0);
            $table->decimal('total_iva_10', 19)->default(0);
            $table->decimal('total_iva', 19)->default(0);
            $table->string('pdf_path', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
