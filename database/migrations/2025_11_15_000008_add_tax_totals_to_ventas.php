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
        Schema::table('ventas', function (Blueprint $table) {
            // Totales por tipo de impuesto (monto total incluyendo IVA)
            $table->decimal('total_exento', 19, 2)->default(0)->after('total');
            $table->decimal('total_gravado_5', 19, 2)->default(0)->after('total_exento');
            $table->decimal('total_gravado_10', 19, 2)->default(0)->after('total_gravado_5');

            // Montos de IVA por tipo
            $table->decimal('total_iva_5', 19, 2)->default(0)->after('total_gravado_10');
            $table->decimal('total_iva_10', 19, 2)->default(0)->after('total_iva_5');
            $table->decimal('total_iva', 19, 2)->default(0)->after('total_iva_10');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn([
                'total_exento',
                'total_gravado_5',
                'total_gravado_10',
                'total_iva_5',
                'total_iva_10',
                'total_iva'
            ]);
        });
    }
};
