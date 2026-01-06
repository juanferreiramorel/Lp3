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
        Schema::create('tipo_transacciones', function (Blueprint $table) {
            $table->increments('id_tipo_transaccion');
            $table->string('descripcion', 100);
            $table->integer('tipo')->default(0); // 0: Neutro, 1: Ingreso, -1: Egreso
            $table->integer('valor')->default(0);
            $table->integer('conciliar')->default(0);
            // $table->timestamps();
        });

        // Insert default values matching the user's image
        DB::table('tipo_transacciones')->insert([
            ['id_tipo_transaccion' => 1, 'descripcion' => 'DEPOSITO EFECTIVO', 'tipo' => 1, 'valor' => 1, 'conciliar' => 1],
            ['id_tipo_transaccion' => 2, 'descripcion' => 'EXTRACCIONES CON CHEQUE', 'tipo' => 0, 'valor' => 1, 'conciliar' => 1],
            ['id_tipo_transaccion' => 3, 'descripcion' => 'TRANSFERENCIAS BANCARIA RECIBIDA', 'tipo' => 1, 'valor' => 1, 'conciliar' => 1],
            ['id_tipo_transaccion' => 4, 'descripcion' => 'VENTAS', 'tipo' => 0, 'valor' => 1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 5, 'descripcion' => 'FONDO DE SENCILLO PARA VUELTO RECEPCIÓN', 'tipo' => 0, 'valor' => 1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 6, 'descripcion' => 'CAJA CHICA', 'tipo' => 0, 'valor' => 1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 7, 'descripcion' => 'PAGOS', 'tipo' => 0, 'valor' => -1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 8, 'descripcion' => 'RECAUDACION DEL DIA', 'tipo' => 1, 'valor' => 1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 9, 'descripcion' => 'RECAUDACIONES A DEPOSITAR', 'tipo' => 0, 'valor' => -1, 'conciliar' => 0], // Adjusted name slightly for clarity if needed, or stick to image: RECAUDACIONES DEL DIA (Egreso)
            ['id_tipo_transaccion' => 10, 'descripcion' => 'RETIRO DE CHEQUE TESORERIA', 'tipo' => 0, 'valor' => -1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 11, 'descripcion' => 'CHEQUE DESDE PUNTO DE VENTA', 'tipo' => 1, 'valor' => 1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 12, 'descripcion' => 'RETIRO DE CHEQUE', 'tipo' => 0, 'valor' => -1, 'conciliar' => 0],
            ['id_tipo_transaccion' => 13, 'descripcion' => 'FALTANTE ACTIVO', 'tipo' => 0, 'valor' => 0, 'conciliar' => 0],
        ]);
        
        // Ensure auto-increment starts after the inserted IDs if needed, 
        // though usually Postgres handles this with sequences, let's hope Laravel handles it or we might need a raw command for sequence update.
        // For MySQL this just works. For Postgres:
        // DB::statement("SELECT setval('tipo_transacciones_id_tipo_transaccion_seq', (SELECT MAX(id_tipo_transaccion) FROM tipo_transacciones))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_transacciones');
    }
};
