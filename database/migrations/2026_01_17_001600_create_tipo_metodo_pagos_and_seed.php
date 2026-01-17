<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla tipo_metodo_pagos
        if (!Schema::hasTable('tipo_metodo_pagos')) {
            Schema::create('tipo_metodo_pagos', function (Blueprint $table) {
                $table->increments('id_tipo_metodo_pago');
                $table->string('descripcion');
                $table->integer('id_transaccion_entrada')->nullable()->comment('ID Transacción para para cobros/ingresos');
                $table->integer('id_transaccion_salida')->nullable()->comment('ID Transacción para pagos/cierre/depósitos');
                $table->boolean('estado')->default(true);
            });
        }

        // 2. Limpiar y Repoblar Transacciones (Empezando de 0 como pidio el user)
        DB::table('tipo_transacciones')->truncate();
        
        // Definir IDs fijos para mejor consistencia en semillas (opcional pero util)
        $transacciones = [
            ['id' => 1, 'desc' => 'VENTA CONTADO', 'valor' => 1],
            ['id' => 2, 'desc' => 'COBRO CLIENTE', 'valor' => 1],
            ['id' => 3, 'desc' => 'TRANSFERENCIA RECIBIDA', 'valor' => 1],
            ['id' => 4, 'desc' => 'CHEQUE RECIBIDO', 'valor' => 1],
            
            ['id' => 5, 'desc' => 'PAGO PROVEEDOR', 'valor' => -1],
            ['id' => 6, 'desc' => 'GASTO / CAJA CHICA', 'valor' => -1],
            ['id' => 7, 'desc' => 'TRANSFERENCIA ENVIADA', 'valor' => -1],
            
            ['id' => 8, 'desc' => 'RECAUDACION A DEPOSITAR', 'valor' => -1],
            ['id' => 9, 'desc' => 'ARQUEO FALTANTE', 'valor' => -1],
            ['id' => 10, 'desc' => 'ARQUEO SOBRANTE', 'valor' => 1],
            ['id' => 11, 'desc' => 'ACREDITACION TARJETA DE CREDITO', 'valor' => 1],
            ['id' => 12, 'desc' => 'ACREDITACION TARJETA DE DEBITO', 'valor' => 1],
        ];

        foreach ($transacciones as $t) {
            DB::table('tipo_transacciones')->insert([
                'id_tipo_transaccion' => $t['id'],
                'descripcion' => $t['desc'],
                'valor' => $t['valor']
            ]);
            // Ajustar secuencia para postgres si es posible, o dejar que nextval se arregle solo despues
        }
         // Fix Postgres sequence
        if(config('database.default') == 'pgsql'){
            DB::statement("SELECT setval('tipo_transacciones_id_tipo_transaccion_seq', " . (count($transacciones) + 1) . ", false)");
        }

        // 3. Poblar Tipo Metodo Pagos con Mapeo
        $tipos = [
            // EFECTIVO: Salida = Recaudacion a Depositar (ID 8)
            ['id' => 1, 'desc' => 'EFECTIVO', 'in' => 1, 'out' => 8], 
            
            // TRANSFERENCIA: Entrada = Transf Recibida (ID 3), Salida = Transf Enviada (ID 7)
            ['id' => 2, 'desc' => 'TRANSFERENCIA BANCARIA', 'in' => 3, 'out' => 7],

            // TARJETA CREDITO: Entrada = Acreditacion TDC (ID 11), Salida = Recaudacion (ID 8)
            ['id' => 3, 'desc' => 'TARJETA DE CREDITO', 'in' => 11, 'out' => 8], 

            // TARJETA DEBITO: Entrada = Acreditacion TDD (ID 12), Salida = Recaudacion (ID 8)
            ['id' => 4, 'desc' => 'TARJETA DE DEBITO', 'in' => 12, 'out' => 8],

            // CHEQUE: Entrada = Cheque Recibido (ID 4)
            ['id' => 5, 'desc' => 'CHEQUE', 'in' => 4, 'out' => null],
        ];

        foreach ($tipos as $tm) {
             DB::table('tipo_metodo_pagos')->insert([
                'id_tipo_metodo_pago' => $tm['id'],
                'descripcion' => $tm['desc'],
                'id_transaccion_entrada' => $tm['in'],
                'id_transaccion_salida' => $tm['out']
            ]);
        }
         if(config('database.default') == 'pgsql'){
            DB::statement("SELECT setval('tipo_metodo_pagos_id_tipo_metodo_pago_seq', " . (count($tipos) + 1) . ", false)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_metodo_pagos');
    }
};
