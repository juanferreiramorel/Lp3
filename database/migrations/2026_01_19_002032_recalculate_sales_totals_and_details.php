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
        // 1. Recalculate details based on Tax Percentage
        \Illuminate\Support\Facades\DB::statement("
            UPDATE detalle_ventas
            SET
                precio_sin_impuesto = CASE
                    WHEN t.porcentaje > 0 THEN detalle_ventas.precio / (1 + (t.porcentaje / 100.0))
                    ELSE detalle_ventas.precio
                END,
                monto_impuesto = CASE
                    WHEN t.porcentaje > 0 THEN detalle_ventas.precio - (detalle_ventas.precio / (1 + (t.porcentaje / 100.0)))
                    ELSE 0
                END
            FROM taxes t
            WHERE detalle_ventas.id_tax = t.id_tax
        ");

        // 2. Recalculate Headers (Sales) based on Details
        \Illuminate\Support\Facades\DB::statement("
            WITH sales_totals AS (
                SELECT
                    dv.id_venta,
                    SUM(CASE WHEN t.porcentaje = 0 THEN (dv.precio * dv.cantidad) ELSE 0 END) as total_exento,
                    SUM(CASE WHEN t.porcentaje = 5 THEN (dv.precio_sin_impuesto * dv.cantidad) ELSE 0 END) as total_gravado_5,
                    SUM(CASE WHEN t.porcentaje = 10 THEN (dv.precio_sin_impuesto * dv.cantidad) ELSE 0 END) as total_gravado_10,
                    SUM(CASE WHEN t.porcentaje = 5 THEN (dv.monto_impuesto * dv.cantidad) ELSE 0 END) as total_iva_5,
                    SUM(CASE WHEN t.porcentaje = 10 THEN (dv.monto_impuesto * dv.cantidad) ELSE 0 END) as total_iva_10,
                    SUM(dv.monto_impuesto * dv.cantidad) as total_iva,
                    SUM(dv.precio * dv.cantidad) as total_cal
                FROM detalle_ventas dv
                JOIN taxes t ON dv.id_tax = t.id_tax
                GROUP BY dv.id_venta
            )
            UPDATE ventas
            SET
                total_exento = st.total_exento,
                total_gravado_5 = st.total_gravado_5,
                total_gravado_10 = st.total_gravado_10,
                total_iva_5 = st.total_iva_5,
                total_iva_10 = st.total_iva_10,
                total_iva = st.total_iva,
                total = st.total_cal
            FROM sales_totals st
            WHERE ventas.id_venta = st.id_venta
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback possible as data is destructively updated without backup
    }
};
