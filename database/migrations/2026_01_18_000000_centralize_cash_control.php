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
        // 1. Add id_apertura to cobros
        Schema::table('cobros', function (Blueprint $table) {
            $table->integer('id_apertura')->nullable();
        });

        // 2. Data Migration: Populate cobros.id_apertura from ventas and notas_debito
        // Note: Using raw SQL for performance and simplicity in joins
        
        // Update from Ventas Linked Cobros
        DB::statement("
            UPDATE cobros c
            SET id_apertura = v.id_apertura
            FROM ventas v
            WHERE c.id_venta = v.id_venta
            AND c.id_apertura IS NULL
        ");

        // Update from Notas Debito Linked Cobros
        DB::statement("
            UPDATE cobros c
            SET id_apertura = nd.id_apertura
            FROM notas_debito nd
            WHERE c.id_nota_debito = nd.id_nota_debito
            AND c.id_apertura IS NULL
        ");

        // 3. Drop id_apertura from ventas and notas_debito
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('id_apertura');
        });

        Schema::table('notas_debito', function (Blueprint $table) {
            $table->dropColumn('id_apertura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add columns back
        Schema::table('ventas', function (Blueprint $table) {
            $table->integer('id_apertura')->nullable();
        });

        Schema::table('notas_debito', function (Blueprint $table) {
            $table->integer('id_apertura')->nullable();
        });

        // 2. Try to restore data (Best Effort)
        // We can restore where we have a direct link. 
        // Note: If multiple cobros exist for one sale with different aperturas (unlikely in old system but possible in new), 
        // we just take one. This is imperfect but acceptable for rollback.
        
        DB::statement("
            UPDATE ventas v
            SET id_apertura = c.id_apertura
            FROM cobros c
            WHERE v.id_venta = c.id_venta
            AND v.id_apertura IS NULL
        ");

        DB::statement("
            UPDATE notas_debito nd
            SET id_apertura = c.id_apertura
            FROM cobros c
            WHERE nd.id_nota_debito = c.id_nota_debito
            AND nd.id_apertura IS NULL
        ");

        // 3. Drop column from cobros
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropColumn('id_apertura');
        });
    }
};
