<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('compras')) {
            return;
        }

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS compras_proveedor_factura_unique
            ON compras (id_proveedor, factura)
            WHERE factura IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('compras')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS compras_proveedor_factura_unique');
    }
};
