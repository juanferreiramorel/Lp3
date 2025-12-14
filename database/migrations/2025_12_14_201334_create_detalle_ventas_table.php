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
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->increments('id_detalle_venta');
            $table->integer('id_venta');
            $table->integer('id_producto');
            $table->integer('cantidad');
            $table->decimal('precio', 19);
            $table->bigInteger('id_tax')->nullable();
            $table->decimal('precio_sin_impuesto', 19)->nullable();
            $table->decimal('monto_impuesto', 19)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
