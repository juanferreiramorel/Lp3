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
        Schema::table('timbrados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo_timbrado')->default(1)->after('id_empresa');
            $table->foreign('id_tipo_timbrado')->references('id_tipo_timbrado')->on('tipos_timbrados');
            
            // Reemplazar restricción única simple por compuesta
            $table->dropUnique('timbrados_numero_timbrado_unique');
            $table->unique(['numero_timbrado', 'id_tipo_timbrado'], 'timbrados_numero_tipo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timbrados', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_timbrado']);
            $table->dropUnique('timbrados_numero_tipo_unique');
            $table->dropColumn('id_tipo_timbrado');
            
            $table->unique('numero_timbrado', 'timbrados_numero_timbrado_unique');
        });
    }
};
