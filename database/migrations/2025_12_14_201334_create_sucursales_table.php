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
        Schema::create('sucursales', function (Blueprint $table) {
            $table->increments('id_sucursal');
            $table->string('descripcion');
            $table->text('direccion')->nullable();
            $table->string('telefono', 40)->nullable();
            $table->integer('id_ciudad')->nullable();
            $table->integer('id_empresa')->nullable()->index('idx_sucursales_empresa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
