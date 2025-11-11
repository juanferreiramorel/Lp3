<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use RealRashid\SweetAlert\Facades\Alert;

class CuentasAPagarController extends Controller
{
    // Definir una variable para el path
    private $path;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:cuentasapagar index')->only(['index', 'detalle', 'obtenerHistorialPagos', 'imprimirRecibo']);
        $this->middleware('permission:cuentasapagar create')->only(['pagar']);
        $this->middleware('permission:cuentasapagar destroy')->only(['anularPago']);
    }
    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND c.fecha_compra BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND c.fecha_compra >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND c.fecha_compra <= '{$fecha_hasta}'";
        }

        // FILTRO POR PROVEEDOR
        if (!empty($input['id_proveedor'])) {
            $sql_filtros .= " AND c.id_proveedor = " . (int)$input['id_proveedor'];
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND c.id_sucursal = " . (int)$input['id_sucursal'];
        }

        // FILTRO POR ESTADO DE CUOTAS
        if (!empty($input['estado_cuota'])) {
            $extracondition = $input['estado_cuota']=='VENCIDO' ? "AND estado = 'PENDIENTE' AND vencimiento < CURRENT_DATE" : "AND estado = '".$input['estado_cuota']."'";
            $sql_filtros .= " AND EXISTS (SELECT 1 FROM cuentas_a_pagar WHERE id_compra = c.id_compra ".$extracondition.")";
        }

        // Obtener el término de búsqueda del request
        $buscar = $request->get('buscar');
        $sql_busqueda = 'WHERE c.condicion_compra = \'CREDITO\' AND c.estado != \'ANULADO\' AND c.estado != \'PAGADO\'';

        // Agrupar por factura para mostrar el resumen
        if (!empty($buscar)) {
            // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd para búsqueda
            $buscarFecha = $buscar;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscar, $matches)) {
                $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }

            // Limpiar separadores de miles para búsqueda de montos
            $buscarMonto = str_replace('.', '', $buscar);

            $sql_busqueda = "WHERE c.condicion_compra = 'CREDITO'
                AND c.estado != 'ANULADO' AND c.estado != 'PAGADO'
                AND (
                    c.factura ILIKE '%{$buscar}%'
                    OR p.descripcion ILIKE '%{$buscar}%'
                    OR cast(c.fecha_compra as text) ILIKE '%{$buscarFecha}%'
                    OR TO_CHAR(c.fecha_compra, 'DD/MM/YYYY') ILIKE '%{$buscar}%'
                    OR cast(c.total as text) ILIKE '%{$buscarMonto}%'
                    OR TO_CHAR(c.total, 'FM999G999G999G999G999') ILIKE '%{$buscar}%'
                )";
        }

        $cuentasapagar = DB::select(
            "SELECT
                c.id_compra,
                c.factura,
                p.descripcion AS proveedor,
                c.fecha_compra,
                c.total AS monto_total,
                COUNT(ca.id_cta) AS total_cuotas,
                SUM(CASE WHEN ca.estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS cuotas_pendientes,
                SUM(CASE WHEN ca.estado = 'PAGADO' THEN 1 ELSE 0 END) AS cuotas_pagadas,
                MIN(ca.vencimiento) AS primer_vencimiento,
                MAX(ca.vencimiento) AS ultimo_vencimiento
            FROM compras c
            JOIN proveedores p USING(id_proveedor)
            JOIN cuentas_a_pagar ca USING(id_compra)
            {$sql_busqueda} {$sql_filtros}
            GROUP BY c.id_compra, c.factura, proveedor, c.fecha_compra, c.total
            ORDER BY c.fecha_compra DESC"
        );

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                   // cantidad de registros por página
        $total = count($cuentasapagar);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($cuentasapagar, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $cuentasapagar = new LengthAwarePaginator(
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
            return view('cuentasapagar.table')->with('cuentasapagar', $cuentasapagar);
        }

        // Obtener métodos de pago para el modal de pago
        $metodos_pago = DB::table('metodo_pagos')->where('estado', true)->pluck('descripcion', 'id_metodo_pago');

        // Obtener datos para los filtros
        $proveedores = DB::table('proveedores')->pluck('descripcion', 'id_proveedor');
        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');
        $estados_cuota = [
            'PENDIENTE' => 'PENDIENTE',
            'VENCIDO' => 'VENCIDO',
            'PAGADO' => 'PAGADO'
        ];

        return view('cuentasapagar.index')
            ->with('cuentasapagar', $cuentasapagar)
            ->with('metodos_pago', $metodos_pago)
            ->with('proveedores', $proveedores)
            ->with('sucursales', $sucursales)
            ->with('estados_cuota', $estados_cuota);
    }

    public function detalle($id_compra)
    {
        // Obtener el detalle de todas las cuentas a pagar de una factura específica
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
                    WHEN ca.estado = \'PENDIENTE\' AND ca.vencimiento < CURRENT_DATE THEN \'VENCIDO\'
                    ELSE ca.estado
                END AS estado_actual
            FROM cuentas_a_pagar ca
            WHERE ca.id_compra = ?
            ORDER BY ca.nro_cuota ASC',
            [$id_compra]
        );

        return response()->json($detalle);
    }

    public function pagar(Request $request, $id_cta)
    {
        // Log de inicio
        Log::info('Iniciando proceso de pago', [
            'id_cta' => $id_cta,
            'datos_request' => $request->all()
        ]);

        // Validar los datos recibidos
        try {
            $request->validate([
                'id_metodo_pago' => 'required|integer',
                'monto' => 'required|string',
                'fecha_pago' => 'required|date',
                'nro_voucher' => 'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación en pago', ['errores' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', array_map(function($errors) {
                    return implode(', ', $errors);
                }, $e->errors()))
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Obtener la cuenta a pagar
            $cuenta = DB::selectOne(
                'SELECT ca.*, c.id_compra
                FROM cuentas_a_pagar ca
                JOIN compras c ON c.id_compra = ca.id_compra
                WHERE ca.id_cta = ?',
                [$id_cta]
            );

            if (empty($cuenta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta a pagar no existe'
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

            // Insertar el pago
            Log::info('Insertando pago', [
                'id_cta' => $id_cta,
                'id_compra' => $cuenta->id_compra,
                'monto' => $monto
            ]);

            $insertResult = DB::insert(
                'INSERT INTO pagos(id_cta, id_compra, user_id, id_metodo_pago, pago_fecha, pago_importe, pago_estado, nro_voucher, created_at, updated_at)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $id_cta,
                    $cuenta->id_compra,
                    auth()->user()->id,
                    $request->id_metodo_pago,
                    $request->fecha_pago,
                    $monto,
                    'PAGADO',
                    empty($request->nro_voucher) ? null : $request->nro_voucher
                ]
            );

            Log::info('Pago insertado correctamente', ['result' => $insertResult]);

            if (!$insertResult) {
                throw new \Exception('No se pudo insertar el pago en la base de datos');
            }

            // Actualizar el importe_pagado y estado de la cuenta a pagar
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
                'UPDATE cuentas_a_pagar SET importe_pagado = ?, estado = ? WHERE id_cta = ?',
                [$nuevo_importe_pagado, $nuevo_estado, $id_cta]
            );

            DB::commit();

            // Obtener el ID del pago recién insertado
            $id_pago = DB::selectOne('SELECT MAX(id_pago) as id FROM pagos WHERE id_cta = ?', [$id_cta])->id;

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'es_pago_completo' => $monto >= $cuenta->importe,
                'id_pago' => $id_pago
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            Log::error('Error al registrar pago de cuenta a pagar: ' . $errorMessage . ' | Línea: ' . $e->getLine() . ' | Archivo: ' . $e->getFile());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $errorMessage
            ], 500);
        }
    }

    public function obtenerHistorialPagos($id_cta)
    {
        try {
            // Obtener todos los pagos de esta cuenta específica
            $historial = DB::select(
                'SELECT
                    p.id_pago,
                    p.pago_fecha,
                    p.pago_importe,
                    p.pago_estado,
                    p.nro_voucher,
                    mp.descripcion AS metodo_pago,
                    u.name AS usuario_registro,
                    ua.name AS usuario_anulo,
                    p.fechahora_anulado
                FROM pagos p
                LEFT JOIN metodo_pagos mp ON mp.id_metodo_pago = p.id_metodo_pago
                LEFT JOIN users u ON u.id = p.user_id
                LEFT JOIN users ua ON ua.id = p.id_useranulado
                WHERE p.id_cta = ?
                ORDER BY p.pago_fecha DESC, p.id_pago DESC',
                [$id_cta]
            );

            return response()->json($historial);
        } catch (\Exception $e) {
            Log::error('Error al obtener historial de pagos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }

    public function anularPago(Request $request, $id_pago)
    {
        DB::beginTransaction();
        try {
            // Obtener el pago a anular
            $pago = DB::selectOne(
                'SELECT * FROM pagos WHERE id_pago = ?',
                [$id_pago]
            );

            if (empty($pago)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pago no existe'
                ], 404);
            }

            // Validar que el pago no esté ya anulado
            if ($pago->pago_estado === 'ANULADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pago ya fue anulado'
                ], 400);
            }

            // Obtener la cuenta a pagar usando el id_cta del pago
            $cuenta = DB::selectOne(
                'SELECT * FROM cuentas_a_pagar WHERE id_cta = ?',
                [$pago->id_cta]
            );

            if (empty($cuenta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la cuenta asociada a este pago'
                ], 404);
            }

            // Marcar el pago como anulado
            DB::update(
                'UPDATE pagos SET pago_estado = ?, id_useranulado = ?, fechahora_anulado = ? WHERE id_pago = ?',
                ['ANULADO', auth()->user()->id, Carbon::now(), $id_pago]
            );

            // Actualizar el importe_pagado de la cuenta
            $nuevo_importe_pagado = ($cuenta->importe_pagado ?? 0) - $pago->pago_importe;
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
                'UPDATE cuentas_a_pagar SET importe_pagado = ?, estado = ? WHERE id_cta = ?',
                [$nuevo_importe_pagado, $nuevo_estado, $cuenta->id_cta]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago anulado correctamente. El saldo de la cuota ha sido actualizado.',
                'nuevo_estado' => $nuevo_estado,
                'nuevo_saldo' => $cuenta->importe - $nuevo_importe_pagado
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular pago: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al anular el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function imprimirRecibo($id_pago)
    {
        try {
            // Obtener datos del pago con toda la información relacionada
            $pago = DB::selectOne(
                'SELECT
                    p.id_pago,
                    p.pago_fecha,
                    p.pago_importe,
                    p.nro_voucher,
                    p.pago_estado,
                    ca.id_cta,
                    ca.nro_cuota,
                    ca.importe as importe_cuota,
                    ca.vencimiento,
                    c.id_compra,
                    c.factura,
                    c.fecha_compra,
                    c.total as total_compra,
                    pr.descripcion as proveedor_nombre,
                    pr.direccion as proveedor_direccion,
                    pr.telefono as proveedor_telefono,
                    mp.descripcion as metodo_pago,
                    u.name as usuario_registro,
                    s.descripcion as sucursal,
                    s.direccion as sucursal_direccion,
                    s.telefono as sucursal_telefono
                FROM pagos p
                INNER JOIN cuentas_a_pagar ca ON ca.id_cta = p.id_cta
                INNER JOIN compras c ON c.id_compra = ca.id_compra
                INNER JOIN proveedores pr ON pr.id_proveedor = c.id_proveedor
                LEFT JOIN metodo_pagos mp ON mp.id_metodo_pago = p.id_metodo_pago
                LEFT JOIN users u ON u.id = p.user_id
                LEFT JOIN sucursales s ON s.id_sucursal = c.id_sucursal
                WHERE p.id_pago = ?',
                [$id_pago]
            );

            if (empty($pago)) {
                abort(404, 'Recibo no encontrado');
            }

            // Verificar si el pago está anulado
            if ($pago->pago_estado === 'ANULADO') {
                return view('cuentasapagar.recibo')->with('pago', $pago)->with('anulado', true);
            }

            return view('cuentasapagar.recibo')->with('pago', $pago)->with('anulado', false);

        } catch (\Exception $e) {
            Log::error('Error al imprimir recibo: ' . $e->getMessage());
            abort(500, 'Error al generar el recibo');
        }
    }
}
