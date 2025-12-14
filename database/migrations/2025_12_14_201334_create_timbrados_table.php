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
        Schema::create('timbrados', function (Blueprint $table) {
            $table->bigIncrements('id_timbrado');
            $table->bigInteger('id_empresa');
            $table->string('numero_timbrado', 50)->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->integer('rango_inicio');
            $table->integer('rango_fin');
            $table->integer('numero_actual')->default(0);
            $table->string('establecimiento', 3)->default('001');
            $table->string('punto_expedicion', 3)->default('001');
            $table->enum('estado', ['VIGENTE', 'VENCIDO', 'AGOTADO', 'CANCELADO'])->default('VIGENTE')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['id_empresa', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timbrados');
    }
};
