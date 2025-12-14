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
        Schema::create('cobros', function (Blueprint $table) {
            $table->increments('id_cobro');
            $table->integer('id_venta')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('id_metodo_pago')->nullable();
            $table->date('cobro_fecha')->nullable();
            $table->float('cobro_importe', null, 0)->nullable();
            $table->string('cobro_estado', 50)->nullable()->default('COBRADO');
            $table->string('nro_voucher', 200)->nullable();
            $table->timestamp('fechahora_anulado')->nullable();
            $table->integer('id_useranulado')->nullable();
            $table->integer('id_cta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
