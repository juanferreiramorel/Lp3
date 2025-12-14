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
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('clie_nombre');
            $table->string('clie_apellido');
            $table->string('clie_ci', 40);
            $table->string('clie_telefono', 40)->nullable();
            $table->text('clie_direccion')->nullable();
            $table->integer('id_ciudad');
            $table->date('clie_fecha_nac')->nullable();
            $table->integer('id_departamento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
