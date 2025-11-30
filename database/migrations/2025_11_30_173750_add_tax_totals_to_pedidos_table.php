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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->decimal('total_exento', 19, 2)->default(0)->after('total_pedido');
            $table->decimal('total_gravado_5', 19, 2)->default(0)->after('total_exento');
            $table->decimal('total_gravado_10', 19, 2)->default(0)->after('total_gravado_5');
            $table->decimal('total_iva_5', 19, 2)->default(0)->after('total_gravado_10');
            $table->decimal('total_iva_10', 19, 2)->default(0)->after('total_iva_5');
            $table->decimal('total_iva', 19, 2)->default(0)->after('total_iva_10');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
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
