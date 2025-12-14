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
        Schema::create('recibos_simples', function (Blueprint $table) {
            $table->comment('Tabla principal de recibos de pago informales');
            $table->increments('id_recibo');
            $table->integer('id_cliente')->index('idx_recibos_cliente');
            $table->integer('id_sucursal')->index('idx_recibos_sucursal');
            $table->integer('user_id')->index('idx_recibos_user');
            $table->date('fecha_recibo')->index('idx_recibos_fecha');
            $table->decimal('total', 19)->comment('Total del recibo en guaraníes');
            $table->string('estado', 20)->default('ACTIVO')->index('idx_recibos_estado')->comment('Estado del recibo: ACTIVO o ANULADO');
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
            $table->timestamp('updated_at')->nullable()->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibos_simples');
    }
};
