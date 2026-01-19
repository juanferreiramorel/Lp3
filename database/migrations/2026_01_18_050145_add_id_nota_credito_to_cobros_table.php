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
            $table->unsignedBigInteger('id_nota_credito')->nullable()->after('id_nota_debito');
            $table->foreign('id_nota_credito')->references('id_nota_credito')->on('notas_credito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropForeign(['id_nota_credito']);
            $table->dropColumn('id_nota_credito');
        });
    }
};
