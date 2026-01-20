<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use RealRashid\SweetAlert\Facades\Alert;

class CuentasACobrarController extends AppBaseController
{
    // Definir una variable para el path
    private $path;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:cuentasacobrar index')->only(['index', 'detalle', 'obtenerHistorialCobros', 'imprimirRecibo']);
        $this->middleware('permission:cuentasacobrar create')->only(['cobrar']);
        $this->middleware('permission:cuentasacobrar destroy')->only(['anularCobro']);
    }
    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND v.fecha_venta BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND v.fecha_venta >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND v.fecha_venta <= '{$fecha_hasta}'";
        }

        // FILTRO POR CLIENTE
        if (!empty($input['id_cliente'])) {
            $sql_filtros .= " AND v.id_cliente = " . (int)$input['id_cliente'];
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND v.id_sucursal = " . (int)$input['id_sucursal'];
        }

        // FILTRO POR ESTADO DE CUOTAS
        if (!empty($input['estado_cuota'])) {
            $extracondition = $input['estado_cuota']=='VENCIDO' ? "AND estado = 'PENDIENTE' AND vencimiento < CURRENT_DATE" : "AND estado = '".$input['estado_cuota']."'";
            $sql_filtros .= " AND EXISTS (SELECT 1 FROM cuentas_a_cobrar WHERE id_venta = v.id_venta ".$extracondition.")";
        }

        // Obtener el término de búsqueda del request
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $sql_busqueda = 'WHERE v.condicion_venta = \'CREDITO\' AND v.estado != \'ANULADO\' AND v.estado != \'PAGADO\'';

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql_busqueda = "WHERE v.condicion_venta = 'CREDITO'
                    AND v.estado != 'ANULADO' AND v.estado != 'PAGADO'
                    AND v.id_venta = " . (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd para búsqueda
                $buscarFecha = $buscarTerm;
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }

                // Limpiar separadores de miles para búsqueda de montos
                $buscarMonto = str_replace('.', '', $buscarTerm);

                $sql_busqueda = "WHERE v.condicion_venta = 'CREDITO'
                    AND v.estado != 'ANULADO' AND v.estado != 'PAGADO'
                    AND (
                        v.factura_nro ILIKE '%{$buscarTerm}%'
                        OR CONCAT(clie_nombre,' ',clie_apellido) ILIKE '%{$buscarTerm}%'
                        OR clie_ci ILIKE '%{$buscarTerm}%'
                        OR cast(v.fecha_venta as text) ILIKE '%{$buscarFecha}%'
                        OR TO_CHAR(v.fecha_venta, 'DD/MM/YYYY') ILIKE '%{$buscarTerm}%'
                        OR cast(v.total as text) ILIKE '%{$buscarMonto}%'
                        OR TO_CHAR(v.total, 'FM999G999G999G999G999') ILIKE '%{$buscarTerm}%'
                    )";
            }
        }

        $cuentasacobrar = DB::select(
            "SELECT
                v.id_venta,
                v.factura_nro,
                CONCAT(clie_nombre,' ',clie_apellido) AS cliente,
                v.fecha_venta,
                v.total AS monto_total,
                COUNT(ca.id_cta) AS total_cuotas,
                SUM(CASE WHEN ca.estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS cuotas_pendientes,
                SUM(CASE WHEN ca.estado = 'PENDIENTE' AND ca.vencimiento < CURRENT_DATE THEN 1 ELSE 0 END) AS cuotas_vencidas_count,
                SUM(CASE WHEN ca.estado = 'PAGADO' THEN 1 ELSE 0 END) AS cuotas_pagadas,
                SUM(ca.importe - COALESCE(ca.importe_pagado, 0)) AS saldo_pendiente,
                MIN(ca.vencimiento) AS primer_vencimiento,
                MAX(ca.vencimiento) AS ultimo_vencimiento
            FROM ventas v
            JOIN clientes USING(id_cliente)
            JOIN cuentas_a_cobrar ca USING(id_venta)
            {$sql_busqueda} {$sql_filtros}
            GROUP BY v.id_venta, v.factura_nro, cliente, v.fecha_venta, v.total
            ORDER BY v.fecha_venta DESC"
        );

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
        // Obtener métodos de pago para el modal de cobro
        $metodos_pago = $this->getMetodosPago();

        // Obtener datos para los filtros
        $clientes = DB::table('clientes')->selectRaw("id_cliente, CONCAT(clie_nombre, ' ', clie_apellido) as cliente")->pluck('cliente', 'id_cliente');
        $sucursales = $this->getSucursalesPermitidas();
        $estados_cuota = [
            'PENDIENTE' => 'PENDIENTE',
            'VENCIDO' => 'VENCIDO',
            'PARCIAL' => 'PARCIAL',
            'PAGADO' => 'PAGADO'
        ];

        // Validar que el usuario tenga una caja abierta
        $caja_abierta = DB::selectOne(
            "SELECT * FROM apertura_cierre_cajas
            WHERE user_id = ? AND estado = 'ABIERTA'",
            [auth()->user()->id]
        );

        // Cajas para apertura (si la caja está cerrada)
        $cajas = DB::table('cajas')
            ->where('id_sucursal', auth()->user()->id_sucursal)
            ->pluck('descripcion', 'id_caja');

        return view('cuentasacobrar.index')
            ->with('cuentasacobrar', $cuentasacobrar)
            ->with('metodos_pago', $metodos_pago)
            ->with('clientes', $clientes)
            ->with('sucursales', $sucursales)
            ->with('caja_abierta', $caja_abierta)
            ->with('estados_cuota', $estados_cuota)
            ->with('cajas', $cajas);
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
                    WHEN ca.estado = \'PENDIENTE\' AND ca.vencimiento < CURRENT_DATE THEN \'VENCIDO\'
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

        // Validar que el usuario tenga una caja abierta
        $caja_abierta = DB::selectOne(
            "SELECT * FROM apertura_cierre_cajas
            WHERE user_id = ? AND estado = 'ABIERTA'",
            [auth()->user()->id]
        );

        if (empty($caja_abierta)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una caja abierta para realizar cobros.'
            ], 403);
        }

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
                'INSERT INTO cobros(id_cta, id_venta, user_id, id_metodo_pago, cobro_fecha, cobro_importe, cobro_estado, nro_voucher, id_apertura)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $id_cta,
                    $cuenta->id_venta,
                    auth()->user()->id,
                    $request->id_metodo_pago,
                    $request->fecha_cobro,
                    $monto,
                    'COBRADO',
                    $request->nro_voucher ?? null,
                    $caja_abierta->id_apertura
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

            // Obtener el ID del cobro recién insertado
            $id_cobro = DB::selectOne('SELECT MAX(id_cobro) as id FROM cobros WHERE id_cta = ?', [$id_cta])->id;

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'es_pago_completo' => $monto >= $cuenta->importe,
                'id_cobro' => $id_cobro
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

    public function formCobrar($id_cta)
    {
        // Validar que el usuario tenga una caja abierta
        $caja_abierta = DB::selectOne(
            "SELECT * FROM apertura_cierre_cajas
            WHERE user_id = ? AND estado = 'ABIERTA'",
            [auth()->user()->id]
        );

        if (empty($caja_abierta)) {
            Alert::error('Error', 'No tienes una caja abierta para realizar cobros.');
            return redirect()->back();
        }

        // Obtener la cuenta con datos relacionados
        $cuenta = DB::table('cuentas_a_cobrar')
            ->where('id_cta', $id_cta)
            ->first();

        if (!$cuenta) {
            Alert::error('Error', 'Cuenta no encontrada');
            return redirect()->back();
        }

        // Validar que no existan cuotas anteriores pendientes
        $cuotasAnterioresPendientes = DB::table('cuentas_a_cobrar')
            ->where('id_venta', $cuenta->id_venta)
            ->where('nro_cuota', '<', $cuenta->nro_cuota)
            ->where('estado', '!=', 'PAGADO') // Asumimos que PARCIAL también bloquea la siguiente? O solo PENDIENTE? 
                                              // Generalmente se exige tener al día la anterior. 
                                              // Si tiene saldo pendiente (PARCIAL), no debería poder pagar la siguiente.
            ->exists();

        if ($cuotasAnterioresPendientes) {
            Alert::error('Error', 'Debe pagar las cuotas anteriores antes de proceder con esta cuota.');
            return redirect()->back();
        }

        $venta = DB::table('ventas')->where('id_venta', $cuenta->id_venta)->first();
        $cliente = DB::table('clientes')->where('id_cliente', $venta->id_cliente)->first();

        $saldo_pendiente = $cuenta->importe - ($cuenta->importe_pagado ?? 0);

        // Obtener métodos de pago
        // Obtener métodos de pago
        $metodos_pago = $this->getMetodosPago();

        return view('cuentasacobrar.pagar', compact('cuenta', 'venta', 'cliente', 'saldo_pendiente', 'metodos_pago'));
    }

    public function procesarMultiple(Request $request)
    {
        $request->validate([
            'id_cta' => 'required|exists:cuentas_a_cobrar,id_cta',
            'fecha_cobro' => 'required|date',
            'pagos' => 'required|array',
            'pagos.*.id_metodo_pago' => 'required|exists:metodo_pagos,id_metodo_pago',
            'pagos.*.monto' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $id_cta = $request->id_cta;
            $fecha_cobro = $request->fecha_cobro;
            $pagos = $request->pagos;

            // Validar que el usuario tenga una caja abierta para procesar pagos
            $caja_abierta = DB::selectOne(
                "SELECT * FROM apertura_cierre_cajas
                WHERE user_id = ? AND estado = 'ABIERTA'
                ORDER BY id_apertura DESC LIMIT 1",
                [auth()->user()->id]
            );

            if (empty($caja_abierta)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No tienes una caja abierta para realizar cobros.'], 403);
            }

            $cuenta = DB::table('cuentas_a_cobrar')->where('id_cta', $id_cta)->lockForUpdate()->first();
            
            // Validar nuevamente cuotas anteriores por seguridad
            $cuotasAnterioresPendientes = DB::table('cuentas_a_cobrar')
                ->where('id_venta', $cuenta->id_venta)
                ->where('nro_cuota', '<', $cuenta->nro_cuota)
                ->where('estado', '!=', 'PAGADO')
                ->exists();

            if ($cuotasAnterioresPendientes) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Existen cuotas anteriores pendientes de pago.'], 400);
            }

            $saldo_actual = $cuenta->importe - ($cuenta->importe_pagado ?? 0);

            $total_pagado_request = 0;
            $cobros_ids = [];

            foreach ($pagos as $pago) {
                $monto = (float)str_replace('.', '', $pago['monto']);
                if ($monto > 0) {
                    $total_pagado_request += $monto;

                    // Insertar Cobro
                    $id_cobro = DB::table('cobros')->insertGetId([
                        'id_cta' => $id_cta,
                        'id_venta' => $cuenta->id_venta,
                        'user_id' => auth()->id(),
                        'id_metodo_pago' => $pago['id_metodo_pago'],
                        'cobro_fecha' => $fecha_cobro,
                        'cobro_importe' => $monto,
                        'cobro_estado' => 'COBRADO',
                        'nro_voucher' => $pago['nro_voucher'] ?? null,
                        'id_apertura' => $caja_abierta->id_apertura,
                    ], 'id_cobro');
                    
                    $cobros_ids[] = $id_cobro;
                }
            }

            if ($total_pagado_request > $saldo_actual) {
                // Permitir pequeño margen de error o rechazar estricto? Rechazamos estricto por ahora.
                // Aunque front valida, back debe validar.
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'El monto total supera el saldo pendiente.'], 400);
            }

            $nuevo_pagado = ($cuenta->importe_pagado ?? 0) + $total_pagado_request;
            
             // Determinar el nuevo estado
            if ($nuevo_pagado >= $cuenta->importe) {
                $nuevo_estado = 'PAGADO';
            } else {
                $nuevo_estado = 'PARCIAL';
            }

            DB::table('cuentas_a_cobrar')->where('id_cta', $id_cta)->update([
                'importe_pagado' => $nuevo_pagado,
                'estado' => $nuevo_estado
            ]);

            DB::commit();

            // Generar URL para recibos. Si es uno, link normal. Si son varios, podrías pasar ?ids=1,2,3
            $ids_param = implode(',', $cobros_ids);
            
            return response()->json([
                'success' => true, 
                'message' => 'Cobro procesado correctamente',
                'recibo_url' => route('cuentasacobrar.recibo', ['id' => $cobros_ids[0], 'ids' => $ids_param]) 
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }

    public function imprimirRecibo(Request $request, $id_cobro)
    {
        try {
            $ids = $request->query('ids');
            
            if ($ids) {
                $ids_array = explode(',', $ids);
            } else {
                $ids_array = [$id_cobro];
            }

            $cobros = [];
            foreach ($ids_array as $id) {
                $cobro = DB::selectOne(
                    'SELECT
                        c.id_cobro,
                        c.cobro_fecha,
                        c.cobro_importe,
                        c.nro_voucher,
                        c.cobro_estado,
                        ca.id_cta,
                        ca.nro_cuota,
                        ca.importe as importe_cuota,
                        ca.vencimiento,
                        v.id_venta,
                        v.factura_nro,
                        v.fecha_venta,
                        v.total as total_venta,
                        CONCAT(cl.clie_nombre, \' \', cl.clie_apellido) as cliente_nombre,
                        cl.clie_ci as cliente_ci,
                        cl.clie_telefono as cliente_telefono,
                        cl.clie_direccion as cliente_direccion,
                        mp.descripcion as metodo_pago,
                        u.name as usuario_registro,
                        s.descripcion as sucursal,
                        s.direccion as sucursal_direccion,
                        s.telefono as sucursal_telefono
                    FROM cobros c
                    INNER JOIN cuentas_a_cobrar ca ON ca.id_cta = c.id_cta
                    INNER JOIN ventas v ON v.id_venta = ca.id_venta
                    INNER JOIN clientes cl ON cl.id_cliente = v.id_cliente
                    LEFT JOIN metodo_pagos mp ON mp.id_metodo_pago = c.id_metodo_pago
                    LEFT JOIN users u ON u.id = c.user_id
                    LEFT JOIN sucursales s ON s.id_sucursal = v.id_sucursal
                    WHERE c.id_cobro = ?',
                    [$id]
                );
                if ($cobro) {
                    $cobros[] = $cobro;
                }
            }

            if (empty($cobros)) {
                abort(404, 'Recibo no encontrado');
            }

            // Usar la vista existente pero pasando array de cobros. 
            // Posiblemente necesitemos adaptar la vista recibo. 
            // Por compatibilidad, si es uno solo pasamos 'cobro', si son varios pasamos 'cobros'.
            // O mejor, adaptamos la vista para que itere si hay 'cobros', o si hay 'cobro' lo trate como tal.
            
            // Asumiremos que la vista actual solo muestra uno. Para impresión múltiple rápida,
            // podemos simplemente pasar el primero como principal y una lista de "pagos_realizados" extra?
            // O mejor: si hay multiples, retornamos una vista nueva o modificada?
            // "lo que quiero ... es al darle pagar imprimira el recibo automaticamente"
            
            // Vamos a pasar $cobros y adaptar la vista recibo ligeramente para que soporte bucle si existe.
            
            return view('cuentasacobrar.recibo')->with('cobros', $cobros)->with('cobro', $cobros[0]);

        } catch (\Exception $e) {
            Log::error('Error al imprimir recibo: ' . $e->getMessage());
            abort(500, 'Error al generar el recibo');
        }
    }
}
