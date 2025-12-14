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
        Schema::create('plantillas_contratos', function (Blueprint $table) {
            $table->comment('Plantillas de contratos con palabras clave para reemplazo automático');
            $table->increments('id_plantilla_contrato');
            $table->string('nombre_plantilla')->comment('Nombre descriptivo de la plantilla');
            $table->text('descripcion')->nullable();
            $table->text('contenido_plantilla')->comment('Contenido de la plantilla con palabras clave en formato --palabra--');
            $table->boolean('activo')->nullable()->default(true)->index('idx_plantillas_contratos_activo')->comment('Indica si la plantilla está activa o archivada');
            $table->timestamp('fecha_creacion')->nullable()->default(DB::raw("now()"));
            $table->timestamp('fecha_modificacion')->nullable()->default(DB::raw("now()"));
            $table->integer('user_id')->nullable()->index('idx_plantillas_contratos_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_contratos');
    }
};
