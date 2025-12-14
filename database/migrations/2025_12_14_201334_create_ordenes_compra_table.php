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
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->increments('id_orden_compra');
            $table->integer('id_proveedor');
            $table->date('fecha_orden');
            $table->decimal('subtotal', 19)->comment('Subtotal sin IVA (exento + gravado_5 + gravado_10)');
            $table->integer('id_sucursal')->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->integer('id_usuario')->nullable();
            $table->text('observaciones')->nullable();
            $table->integer('id_compra')->nullable();
            $table->decimal('total_exento', 19)->default(0)->comment('Total de productos exentos de IVA');
            $table->decimal('total_gravado_5', 19)->default(0)->comment('Base imponible gravada al 5%');
            $table->decimal('total_gravado_10', 19)->default(0)->comment('Base imponible gravada al 10%');
            $table->decimal('total_iva_5', 19)->default(0)->comment('Monto del IVA al 5%');
            $table->decimal('total_iva_10', 19)->default(0)->comment('Monto del IVA al 10%');
            $table->decimal('total_iva', 19)->default(0)->comment('Total IVA (suma de 5% y 10%)');
            $table->decimal('total', 19)->default(0)->comment('Total final (subtotal + total_iva)');
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
            $table->timestamp('updated_at')->nullable()->default(DB::raw("now()"));
            $table->string('pdf_path', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
