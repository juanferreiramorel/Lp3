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
        Schema::create('contratos_generados', function (Blueprint $table) {
            $table->comment('Historial de contratos generados a partir de plantillas');
            $table->increments('id_contrato_generado');
            $table->integer('id_plantilla_contrato')->index('idx_contratos_generados_plantilla');
            $table->integer('id_cliente')->index('idx_contratos_generados_cliente');
            $table->text('contenido_generado')->comment('Contenido del contrato con todas las palabras clave reemplazadas');
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_generacion')->nullable()->default(DB::raw("now()"))->index('idx_contratos_generados_fecha');
            $table->integer('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos_generados');
    }
};
