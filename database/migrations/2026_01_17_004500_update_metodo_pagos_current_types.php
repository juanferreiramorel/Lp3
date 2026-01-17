<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. EFECTIVO
        DB::table('metodo_pagos')
            ->where('descripcion', 'LIKE', '%EFECTIVO%')
            ->update(['id_tipo_metodo_pago' => 1]);

        // 2. TRANSFERENCIA
        DB::table('metodo_pagos')
            ->where('descripcion', 'LIKE', '%TRANSFERENCIA%')
            ->update(['id_tipo_metodo_pago' => 2]);

        // 3. TARJETA CREDITO
        DB::table('metodo_pagos')
            ->where('descripcion', 'LIKE', '%CREDITO%')
            ->update(['id_tipo_metodo_pago' => 3]);

        // 4. TARJETA DEBITO
        DB::table('metodo_pagos')
            ->where('descripcion', 'LIKE', '%DEBITO%')
            ->update(['id_tipo_metodo_pago' => 4]);

        // 5. CHEQUE
        DB::table('metodo_pagos')
            ->where('descripcion', 'LIKE', '%CHEQUE%')
            ->update(['id_tipo_metodo_pago' => 5]);
    }

    public function down(): void
    {
        // No revert logic needed mainly
    }
};
