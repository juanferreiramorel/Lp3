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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id('id_tax');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('porcentaje', 5, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo', 'idx_taxes_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
