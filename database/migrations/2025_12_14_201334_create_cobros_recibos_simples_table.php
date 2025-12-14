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
        Schema::create('cobros_recibos_simples', function (Blueprint $table) {
            $table->comment('Registro de cobros/pagos asociados a recibos');
            $table->increments('id_cobro_recibo');
            $table->integer('id_recibo')->index('idx_cobro_recibo');
            $table->integer('id_metodo_pago');
            $table->decimal('importe', 19);
            $table->string('nro_voucher', 50)->nullable()->comment('Número de comprobante o voucher de pago');
            $table->text('observacion')->nullable();
            $table->date('fecha_cobro');
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobros_recibos_simples');
    }
};
