<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use RealRashid\SweetAlert\Facades\Alert;

class CuentasACobrarController extends Controller
{
    // Definir una variable para el path
    private $path;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:cuentasacobrar index')->only(['index']);
    }
    public function index(Request $request)
    {
        // Obtener el término de búsqueda del request
        $buscar = $request->get('buscar');

        // Agrupar por factura para mostrar el resumen
        if (!empty($buscar)) {
            // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd para búsqueda
            $buscarFecha = $buscar;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscar, $matches)) {
                $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }

            // Limpiar separadores de miles para búsqueda de montos
            $buscarMonto = str_replace('.', '', $buscar);

            $cuentasacobrar = DB::select(
                'SELECT
                    v.id_venta,
                    v.factura_nro,
                    CONCAT(clie_nombre,\' \',clie_apellido) AS cliente,
                    v.fecha_venta,
                    v.total AS monto_total,
                    COUNT(ca.id_cta) AS total_cuotas,
                    SUM(CASE WHEN ca.estado = \'PENDIENTE\' THEN 1 ELSE 0 END) AS cuotas_pendientes,
                    SUM(CASE WHEN ca.estado = \'PAGADO\' THEN 1 ELSE 0 END) AS cuotas_pagadas,
                    MIN(ca.vencimiento) AS primer_vencimiento,
                    MAX(ca.vencimiento) AS ultimo_vencimiento
                FROM ventas v
                JOIN clientes USING(id_cliente)
                JOIN cuentas_a_cobrar ca USING(id_venta)
                WHERE v.condicion_venta = \'CREDITO\'
                AND (
                    v.factura_nro ILIKE ?
                    OR CONCAT(clie_nombre,\' \',clie_apellido) ILIKE ?
                    OR clie_ci ILIKE ?
                    OR cast(v.fecha_venta as text) ILIKE ?
                    OR TO_CHAR(v.fecha_venta, \'DD/MM/YYYY\') ILIKE ?
                    OR cast(v.total as text) ILIKE ?
                    OR TO_CHAR(v.total, \'FM999G999G999G999G999\') ILIKE ?
                )
                AND v.estado != \'ANULADO\' AND v.estado != \'PAGADO\'
                GROUP BY v.id_venta, v.factura_nro, cliente, v.fecha_venta, v.total
                ORDER BY v.fecha_venta DESC',
                [
                    '%' . $buscar . '%',      // factura_nro
                    '%' . $buscar . '%',      // cliente
                    '%' . $buscar . '%',      // clie_ci
                    '%' . $buscarFecha . '%', // fecha_venta (formato BD)
                    '%' . $buscar . '%',      // fecha_venta (formato DD/MM/YYYY)
                    '%' . $buscarMonto . '%', // total (sin separadores)
                    '%' . $buscar . '%'       // total (con separadores)
                ]
            );
        } else {
            $cuentasacobrar = DB::select(
                'SELECT
                    v.id_venta,
                    v.factura_nro,
                    CONCAT(clie_nombre,\' \',clie_apellido) AS cliente,
                    v.fecha_venta,
                    v.total AS monto_total,
                    COUNT(ca.id_cta) AS total_cuotas,
                    SUM(CASE WHEN ca.estado = \'PENDIENTE\' THEN 1 ELSE 0 END) AS cuotas_pendientes,
                    SUM(CASE WHEN ca.estado = \'PAGADO\' THEN 1 ELSE 0 END) AS cuotas_pagadas,
                    MIN(ca.vencimiento) AS primer_vencimiento,
                    MAX(ca.vencimiento) AS ultimo_vencimiento
                FROM ventas v
                JOIN clientes USING(id_cliente)
                JOIN cuentas_a_cobrar ca USING(id_venta)
                WHERE v.condicion_venta = \'CREDITO\'
                AND v.estado != \'ANULADO\' AND v.estado != \'PAGADO\'
                GROUP BY v.id_venta, v.factura_nro, cliente, v.fecha_venta, v.total
                ORDER BY v.fecha_venta DESC'
            );
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                   // cantidad de registros por página
        $total = count($cuentasacobrar);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($cuentasacobrar, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $cuentasacobrar = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

         // Verificamos si la petición es AJAX
        if ($request->ajax()) { //devuelve true o false si es ajax o no
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('cuentasacobrar.table')->with('cuentasacobrar', $cuentasacobrar);
        }

        // Obtener métodos de pago para el modal de cobro
        $metodos_pago = DB::table('metodo_pagos')->where('estado', true)->pluck('descripcion', 'id_metodo_pago');

        return view('cuentasacobrar.index')
            ->with('cuentasacobrar', $cuentasacobrar)
            ->with('metodos_pago', $metodos_pago);
    }

    public function detalle($id_venta)
    {
        // Obtener el detalle de todas las cuentas a cobrar de una factura específica
        $detalle = DB::select(
            'SELECT
                ca.id_cta AS nro_cuenta,
                ca.nro_cuota,
                ca.importe,
                COALESCE(ca.importe_pagado, 0) AS importe_pagado,
                (ca.importe - COALESCE(ca.importe_pagado, 0)) AS saldo,
                ca.vencimiento,
                ca.estado,
                CASE
                    WHEN ca.estado = \'PAGADO\' THEN \'PAGADO\'
                    WHEN ca.estado = \'PARCIAL\' THEN \'PARCIAL\'
                    WHEN ca.estado = \'PENDIENTE\' AND ca.vencimiento < CURRENT_DATE THEN \'VENCIDA\'
                    ELSE ca.estado
                END AS estado_actual
            FROM cuentas_a_cobrar ca
            WHERE ca.id_venta = ?
            ORDER BY ca.nro_cuota ASC',
            [$id_venta]
        );

        return response()->json($detalle);
    }

    public function cobrar(Request $request, $id_cta)
    {
        // Validar los datos recibidos
        $request->validate([
            'id_metodo_pago' => 'required|integer',
            'monto' => 'required|string',
            'fecha_cobro' => 'required|date',
            'nro_voucher' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Obtener la cuenta a cobrar
            $cuenta = DB::selectOne(
                'SELECT ca.*, v.id_venta
                FROM cuentas_a_cobrar ca
                JOIN ventas v ON v.id_venta = ca.id_venta
                WHERE ca.id_cta = ?',
                [$id_cta]
            );

            if (empty($cuenta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta a cobrar no existe'
                ], 404);
            }

            // Validar que la cuenta no esté completamente pagada
            if ($cuenta->estado === 'PAGADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cuenta ya fue pagada completamente'
                ], 400);
            }

            // Validar método de pago
            $metodo_pago = DB::selectOne(
                'SELECT * FROM metodo_pagos WHERE id_metodo_pago = ?',
                [$request->id_metodo_pago]
            );

            if (empty($metodo_pago)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El método de pago no existe'
                ], 400);
            }

            // Limpiar y convertir el monto
            $monto = str_replace('.', '', $request->monto);
            $monto = (float)$monto;

            // Validar que el monto sea mayor a 0
            if ($monto <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto debe ser mayor a 0'
                ], 400);
            }

            // Calcular el saldo restante de la cuenta
            $importe_pagado_actual = $cuenta->importe_pagado ?? 0;
            $saldo_restante = $cuenta->importe - $importe_pagado_actual;

            // Validar que el monto no exceda el saldo restante
            if ($monto > $saldo_restante) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto a pagar no puede ser mayor al saldo restante de la cuota (' . number_format($saldo_restante, 0, ',', '.') . ')'
                ], 400);
            }

            // Insertar el cobro
            DB::insert(
                'INSERT INTO cobros(id_cta, id_venta, user_id, id_metodo_pago, cobro_fecha, cobro_importe, cobro_estado, nro_voucher)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $id_cta,
                    $cuenta->id_venta,
                    auth()->user()->id,
                    $request->id_metodo_pago,
                    $request->fecha_cobro,
                    $monto,
                    'COBRADO',
                    $request->nro_voucher ?? null
                ]
            );

            // Actualizar el importe_pagado y estado de la cuenta a cobrar
            $importe_pagado_actual = $cuenta->importe_pagado ?? 0;
            $nuevo_importe_pagado = $importe_pagado_actual + $monto;

            // Determinar el nuevo estado
            if ($nuevo_importe_pagado >= $cuenta->importe) {
                $nuevo_estado = 'PAGADO';
                $mensaje = 'Pago registrado correctamente. La cuota ha sido pagada completamente.';
            } else {
                $nuevo_estado = 'PARCIAL';
                $saldo_restante = $cuenta->importe - $nuevo_importe_pagado;
                $mensaje = 'Pago parcial registrado correctamente. Saldo pendiente: ' . number_format($saldo_restante, 0, ',', '.');
            }

            // Actualizar la cuenta
            DB::update(
                'UPDATE cuentas_a_cobrar SET importe_pagado = ?, estado = ? WHERE id_cta = ?',
                [$nuevo_importe_pagado, $nuevo_estado, $id_cta]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'es_pago_completo' => $monto >= $cuenta->importe
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cobro de cuenta a cobrar: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerHistorialCobros($id_cta)
    {
        try {
            // Obtener todos los cobros de esta cuenta específica
            $historial = DB::select(
                'SELECT
                    c.id_cobro,
                    c.cobro_fecha,
                    c.cobro_importe,
                    c.cobro_estado,
                    c.nro_voucher,
                    mp.descripcion AS metodo_pago,
                    u.name AS usuario_registro,
                    ua.name AS usuario_anulo,
                    c.fechahora_anulado
                FROM cobros c
                LEFT JOIN metodo_pagos mp ON mp.id_metodo_pago = c.id_metodo_pago
                LEFT JOIN users u ON u.id = c.user_id
                LEFT JOIN users ua ON ua.id = c.id_useranulado
                WHERE c.id_cta = ?
                ORDER BY c.cobro_fecha DESC, c.id_cobro DESC',
                [$id_cta]
            );

            return response()->json($historial);
        } catch (\Exception $e) {
            Log::error('Error al obtener historial de cobros: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }

    public function anularCobro(Request $request, $id_cobro)
    {
        DB::beginTransaction();
        try {
            // Obtener el cobro a anular
            $cobro = DB::selectOne(
                'SELECT * FROM cobros WHERE id_cobro = ?',
                [$id_cobro]
            );

            if (empty($cobro)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cobro no existe'
                ], 404);
            }

            // Validar que el cobro no esté ya anulado
            if ($cobro->cobro_estado === 'ANULADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cobro ya fue anulado'
                ], 400);
            }

            // Obtener la cuenta a cobrar usando el id_cta del cobro
            $cuenta = DB::selectOne(
                'SELECT * FROM cuentas_a_cobrar WHERE id_cta = ?',
                [$cobro->id_cta]
            );

            if (empty($cuenta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la cuenta asociada a este cobro'
                ], 404);
            }

            // Marcar el cobro como anulado
            DB::update(
                'UPDATE cobros SET cobro_estado = ?, id_useranulado = ?, fechahora_anulado = ? WHERE id_cobro = ?',
                ['ANULADO', auth()->user()->id, Carbon::now(), $id_cobro]
            );

            // Actualizar el importe_pagado de la cuenta
            $nuevo_importe_pagado = ($cuenta->importe_pagado ?? 0) - $cobro->cobro_importe;
            $nuevo_importe_pagado = max(0, $nuevo_importe_pagado); // No puede ser negativo

            // Determinar el nuevo estado de la cuenta
            if ($nuevo_importe_pagado == 0) {
                $nuevo_estado = 'PENDIENTE';
            } elseif ($nuevo_importe_pagado >= $cuenta->importe) {
                $nuevo_estado = 'PAGADO';
            } else {
                $nuevo_estado = 'PARCIAL';
            }

            // Actualizar la cuenta
            DB::update(
                'UPDATE cuentas_a_cobrar SET importe_pagado = ?, estado = ? WHERE id_cta = ?',
                [$nuevo_importe_pagado, $nuevo_estado, $cuenta->id_cta]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cobro anulado correctamente. El saldo de la cuota ha sido actualizado.',
                'nuevo_estado' => $nuevo_estado,
                'nuevo_saldo' => $cuenta->importe - $nuevo_importe_pagado
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular cobro: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al anular el cobro: ' . $e->getMessage()
            ], 500);
        }
    }
}
