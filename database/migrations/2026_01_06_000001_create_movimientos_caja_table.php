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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->increments('id_movimiento');
            $table->integer('id_apertura')->default(0);
            $table->decimal('monto', 19, 2)->default(0);
            $table->dateTime('fecha_hora')->useCurrent();
            $table->integer('id_tipo_transaccion')->default(0);
            $table->integer('id_forma_pago')->default(1);
            $table->string('documento', 150)->nullable()->default('');
            $table->integer('id_factura')->default(0);
            $table->integer('cierre')->default(0); // 1 if it is a closing movement
            $table->integer('id_aperturacaja')->default(0); // Seems redundant with id_apertura but keeping for compatibility with user request
            $table->integer('id_ordendeposito')->default(0);
            $table->integer('id_usuario');
            // Indexes if needed
            // $table->index('id_apertura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
