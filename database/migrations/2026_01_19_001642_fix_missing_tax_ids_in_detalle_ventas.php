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
        \Illuminate\Support\Facades\DB::statement("
            UPDATE detalle_ventas
            SET id_tax = productos.id_tax
            FROM productos
            WHERE detalle_ventas.id_producto = productos.id_producto
            AND detalle_ventas.id_tax IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback
    }
};
