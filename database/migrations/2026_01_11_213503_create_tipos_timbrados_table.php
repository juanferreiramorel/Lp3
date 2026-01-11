<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipos_timbrados', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_timbrado');
            $table->string('descripcion');
            $table->timestamps();
        });

        // Seed data
        DB::table('tipos_timbrados')->insert([
            ['id_tipo_timbrado' => 1, 'descripcion' => 'Factura', 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo_timbrado' => 2, 'descripcion' => 'Nota de Crédito', 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo_timbrado' => 3, 'descripcion' => 'Nota de Débito', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_timbrados');
    }
};
