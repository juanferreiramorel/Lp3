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
        Schema::table('cobros', function (Blueprint $table) {
            $table->integer('id_nota_debito')->nullable()->after('id_venta');
            // Assuming foreign key already exists logic in DB or will be enforced by app
            // Adding explicit FK if possible, but safely.
            // $table->foreign('id_nota_debito')->references('id_nota_debito')->on('notas_debito');
        });

        Schema::table('cuentas_a_cobrar', function (Blueprint $table) {
            $table->integer('id_nota_debito')->nullable()->after('id_venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropColumn('id_nota_debito');
        });

        Schema::table('cuentas_a_cobrar', function (Blueprint $table) {
            $table->dropColumn('id_nota_debito');
        });
    }
};
