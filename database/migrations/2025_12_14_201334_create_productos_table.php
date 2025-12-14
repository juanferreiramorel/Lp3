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
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->string('descripcion');
            $table->decimal('precio', 19);
            $table->integer('id_marca')->nullable();
            $table->string('imagen_producto', 199)->nullable();
            $table->boolean('controlar_stock')->default(true);
            $table->bigInteger('id_tax')->nullable();
            $table->string('codigo_barras', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
