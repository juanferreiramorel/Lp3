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
            $table->id('id_timbrado');
            $table->unsignedBigInteger('id_empresa');
            $table->string('numero_timbrado', 50)->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->integer('rango_inicio');
            $table->integer('rango_fin');
            $table->integer('numero_actual')->default(0);
            $table->string('establecimiento', 3)->default('001');
            $table->string('punto_expedicion', 3)->default('001');
            $table->enum('estado', ['VIGENTE', 'VENCIDO', 'AGOTADO', 'CANCELADO'])->default('VIGENTE');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_empresa')->references('id_empresa')->on('empresas')->onDelete('cascade');

            // Índices
            $table->index('estado');
            $table->index(['id_empresa', 'estado']);
        });

        // Agregar columna id_timbrado a la tabla ventas
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_timbrado')->nullable()->after('id_sucursal');

            $table->foreign('id_timbrado')->references('id_timbrado')->on('timbrados')->onDelete('set null');
            $table->index('id_timbrado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['id_timbrado']);
            $table->dropColumn('id_timbrado');
        });

        Schema::dropIfExists('timbrados');
    }
};
