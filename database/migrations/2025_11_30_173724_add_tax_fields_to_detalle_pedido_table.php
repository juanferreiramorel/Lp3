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
        Schema::table('detalle_pedido', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tax')->nullable()->after('precio_unitario');
            $table->decimal('precio_sin_impuesto', 19, 2)->nullable()->after('id_tax');
            $table->decimal('monto_impuesto', 19, 2)->nullable()->after('precio_sin_impuesto');

            $table->foreign('id_tax', 'detalle_pedido_id_tax_foreign')
                  ->references('id_tax')
                  ->on('taxes')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_pedido', function (Blueprint $table) {
            $table->dropForeign('detalle_pedido_id_tax_foreign');
            $table->dropColumn(['id_tax', 'precio_sin_impuesto', 'monto_impuesto']);
        });
    }
};
