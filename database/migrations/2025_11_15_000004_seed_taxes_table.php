<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insertar impuestos de ejemplo comunes en Paraguay
        DB::table('taxes')->insert([
            [
                'nombre' => 'Exento',
                'descripcion' => 'Productos exentos de impuestos',
                'porcentaje' => 0.00,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'IVA 5%',
                'descripcion' => 'Impuesto al Valor Agregado del 5%',
                'porcentaje' => 5.00,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'IVA 10%',
                'descripcion' => 'Impuesto al Valor Agregado del 10%',
                'porcentaje' => 10.00,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('taxes')->whereIn('nombre', ['Exento', 'IVA 5%', 'IVA 10%'])->delete();
    }
};
