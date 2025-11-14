<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tax')->nullable()->after('precio');
            $table->decimal('precio_sin_impuesto', 19, 2)->nullable()->after('id_tax');
            $table->decimal('monto_impuesto', 19, 2)->nullable()->after('precio_sin_impuesto');

            $table->foreign('id_tax')->references('id_tax')->on('taxes')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropForeign(['id_tax']);
            $table->dropColumn(['id_tax', 'precio_sin_impuesto', 'monto_impuesto']);
        });
    }
};
