<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_orden_compra', function (Blueprint $table) {
            $table->increments('id_detalle_orden');
            $table->integer('id_orden_compra')->index('idx_detalle_oc_orden');
            $table->integer('id_producto')->index('idx_detalle_oc_producto');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 19);
            $table->decimal('subtotal', 19);
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
            $table->timestamp('updated_at')->nullable()->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_orden_compra');
    }
};
