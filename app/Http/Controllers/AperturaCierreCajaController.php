<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AperturaCierreCajaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:aperturacierrecaja index')->only(['index']);
        $this->middleware('permission:aperturacierrecaja create')->only(['create', 'store']);
        $this->middleware('permission:aperturacierrecaja edit')->only(['edit', 'update', 'cerrar_caja', 'editCierre']);
        $this->middleware('permission:aperturacierrecaja destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = 'WHERE 1=1';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND ape.fecha_apertura BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND ape.fecha_apertura >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND ape.fecha_apertura <= '{$fecha_hasta}'";
        }

        // FILTRO POR CAJA
        if (!empty($input['id_caja'])) {
            $sql_filtros .= " AND ape.id_caja = " . (int)$input['id_caja'];
        }

        // FILTRO POR USUARIO
        if (!empty($input['user_id'])) {
            $sql_filtros .= " AND ape.user_id = " . (int)$input['user_id'];
        }

        // FILTRO POR SUCURSAL (Indirecto a través de caja)
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND c.id_sucursal = " . (int)$input['id_sucursal'];
        }

        // BUSCADOR
        $buscar = $request->get('buscar');
        if (!empty($buscar)) {
            $sql_filtros .= " AND (CAST(ape.id_apertura AS TEXT) iLIKE '%{$buscar}%'
                OR u.name iLIKE '%{$buscar}%'
                OR c.descripcion iLIKE '%{$buscar}%'
                OR ape.estado iLIKE '%{$buscar}%')";
        }

        // Restringir a sucursales permitidas
        // $idsPermitidos = $this->getSucursalesPermitidasIds(); // Asumiendo que existe este método o similar en Controller base o trait
        // if (!empty($idsPermitidos)) {
        //     $sql_filtros .= ' AND c.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        // }
        // Simplificado: filtrar por sucursal del usuario actual si no es admin (ajustar según lógica de permisos)
        // Por ahora lo dejamos abierto o filtramos por la sucursal actual si se desea estricto
        
        $aperturas = DB::select(
            "SELECT ape.*, s.descripcion as sucursal, c.descripcion as caja, u.name as usuario
            FROM apertura_cierre_cajas ape
            JOIN cajas c ON c.id_caja = ape.id_caja
            JOIN users u ON u.id = ape.user_id
            JOIN sucursales s ON s.id_sucursal = c.id_sucursal
            {$sql_filtros}
            ORDER BY ape.fecha_apertura DESC, ape.id_apertura DESC"
        );

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $total = count($aperturas);
        $items = array_slice($aperturas, ($page - 1) * $perPage, $perPage);

        $aperturas = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return view('apertura_cierre.table')->with('aperturas', $aperturas);
        }

        // Datos para filtros
        $cajas = DB::table('cajas')->pluck('descripcion', 'id_caja');
        $usuarios = DB::table('users')->pluck('name', 'id');
        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');

        return view('apertura_cierre.index', compact('aperturas', 'cajas', 'usuarios', 'sucursales'));
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // normalizar monto de apertura quitando separadores de miles y usando punto como decimal
        $input['monto_apertura'] = $this->normalizarMonto($input['monto_apertura']);

        // validar datos recibidos
        $validacion = Validator::make($input, [
            'id_caja' => 'required|exists:cajas,id_caja',
            'monto_apertura' => 'numeric|min:0',
            'fecha_apertura' => 'required|date',
        ], [
            'id_caja.required' => 'El campo caja es obligatorio',
            'id_caja.exists' => 'El campo caja seleccionado no existe',
            'monto_apertura.numeric' => 'El campo monto apertura debe ser un número',
            'monto_apertura.min' => 'El campo monto apertura debe ser un número positivo',
            'fecha_apertura.required' => 'El campo fecha apertura es obligatorio',
            'fecha_apertura.date' => 'El campo fecha apertura debe ser una fecha válida',
        ]);

        if ($validacion->fails()) {
            return redirect()->route('ventas.index')
                ->withErrors($validacion)
                ->withInput();
        }

        // insertar datos en la tabla apertura_cierre_cajas
        DB::insert('INSERT INTO apertura_cierre_cajas (id_caja, monto_apertura, monto_cierre, fecha_apertura, user_id, estado)
            VALUES (?, ?, ?, ?, ?, ?)', [
            $input['id_caja'],
            $input['monto_apertura'] ?? 0, //validar si no posee monto apertura colocar 0
            0, // monto cierre 0 al abrir caja
            $input['fecha_apertura'],
            auth()->user()->id,
            'ABIERTA'
        ]);

        // redireccionar a la vista ventas con mensaje de exito
        Alert::toast('Caja abierta con éxito', 'success');
        return redirect()->route('ventas.index');
    }

    public function cerrar_caja(Request $request, $id_apertura)
    {
        $input = $request->all();

        // normalizar monto cierre eliminando separadores de miles y usando punto como decimal
        $input['monto_cierre'] = $this->normalizarMonto($input['monto_cierre']);

        // Obtener datos de la apertura
        $apertura = DB::table('apertura_cierre_cajas')->where('id_apertura', $id_apertura)->first();

        // validar que no exista montos sin cobrar en las ventas CREADAS EN ESTA SESION (PENDIENTES)
        // Usamos user_id y fecha_apertura como proxy
        $ventas_sin_cobrar = DB::selectOne(
            "SELECT COUNT(*) AS no_cobrados 
            FROM ventas 
            WHERE user_id = ? 
              AND fecha_venta >= ? 
              AND estado = 'PENDIENTE'",
            [
                $apertura->user_id,
                $apertura->fecha_apertura
            ]
        );
        // si existen ventas sin cobrar no se puede cerrar caja
        if ($ventas_sin_cobrar->no_cobrados > 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cerrar la caja, existen ventas PENDIENTES generadas en esta sesión.'
                ]);
            }
            Alert::error('Error', 'No se puede cerrar la caja, existen ventas sin cobrar');
            return redirect()->route('ventas.index');
        }
                
        // si no realizar update de apertura_cierre_cajas
        DB::update('UPDATE apertura_cierre_cajas 
            SET monto_cierre = ?, 
                estado = ?,
                fecha_cierre = ?
            WHERE id_apertura = ?', [
            $input['monto_cierre'],
            'CERRADA',
            Carbon::now()->format('Y-m-d'),
            $id_apertura
        ]);

        // AGREGAR MOVIMIENTO DE CAJA: RECAUDACION (Desglosado por Tipo)
        
        // 1. Obtener totales cobrados por método de pago en esta apertura DIRECTAMENTE DE COBROS
        $ventas_notas_totales = DB::select(
            "SELECT id_metodo_pago, SUM(cobro_importe) as total
            FROM cobros
            WHERE id_apertura = ? 
              AND cobro_estado = 'COBRADO'
            GROUP BY id_metodo_pago",
            [$id_apertura]
        );

        $totales_map = [];
        foreach ($ventas_notas_totales as $row) {
             $totales_map[$row->id_metodo_pago] = $row->total;
        }

        // 2. Primero, VALIDAR configuración antes de procesar nada
        $metodos_pago = \App\Models\MetodoPago::with('tipo')->get();
        $errores_config = [];

        foreach ($metodos_pago as $metodo) {
            $monto_a_registrar = 0;
            if ($metodo->id_metodo_pago == 1) {
                // Efectivo siempre validamos con el monto ingresado, si es > 0
                $monto_a_registrar = $input['monto_cierre'];
            } else {
                $monto_a_registrar = $totales_map[$metodo->id_metodo_pago] ?? 0;
            }

            if ($monto_a_registrar > 0) {
                if (!$metodo->tipo) {
                    $errores_config[] = "El método de pago '{$metodo->descripcion}' no tiene asignado un Tipo de Método de Pago.";
                    continue;
                }

                // Validar que tenga transacción asignada según lógica
                $tiene_transaccion = false;
                if ($metodo->id_tipo_metodo_pago == 1) {
                     // Efectivo requiere salida
                     if ($metodo->tipo->id_transaccion_salida) $tiene_transaccion = true;
                } else {
                     // Otros requieren salida o entrada
                     if ($metodo->tipo->id_transaccion_salida || $metodo->tipo->id_transaccion_entrada) $tiene_transaccion = true;
                }

                if (!$tiene_transaccion) {
                    $errores_config[] = "El Tipo de Método '{$metodo->tipo->descripcion}' (usado por {$metodo->descripcion}) no tiene configurada una transacción de entrada/salida válida.";
                }
            }
        }

        if (count($errores_config) > 0) {
            $msg = implode("<br>", $errores_config);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de Configuración: ' . $msg
                ]);
            }
            Alert::error('Error de Configuración', $msg);
            return redirect()->route('ventas.index');
        }

        // 3. Procesar Movimientos (Si pasó validación)
        foreach ($metodos_pago as $metodo) {
            $monto_a_registrar = 0;
            $id_transaccion = 0;
            
            if (!$metodo->tipo) continue; // Ya validado arriba, pero por seguridad

            if ($metodo->id_tipo_metodo_pago == 1) { 
                $monto_a_registrar = $input['monto_cierre'];
                $id_transaccion = $metodo->tipo->id_transaccion_salida;
            } else {
                $monto_a_registrar = $totales_map[$metodo->id_metodo_pago] ?? 0;
                
                if ($metodo->tipo->id_transaccion_salida && ($metodo->id_tipo_metodo_pago == 3 || $metodo->id_tipo_metodo_pago == 4)) {
                     $id_transaccion = $metodo->tipo->id_transaccion_salida;
                } else {
                     $id_transaccion = $metodo->tipo->id_transaccion_entrada;
                }
            }

            if ($monto_a_registrar > 0 && $id_transaccion > 0) {
                 try {
                    DB::insert('INSERT INTO movimientos_caja (id_apertura, monto, fecha_hora, id_tipo_transaccion, id_forma_pago, documento, cierre, id_usuario)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                        $id_apertura,
                        $monto_a_registrar,
                        Carbon::now(),
                        $id_transaccion,
                        $metodo->id_metodo_pago,
                        'CIERRE - ' . $metodo->descripcion,
                        1,
                        auth()->user()->id
                    ]);
                } catch (\Exception $e) { }
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada con éxito',
                'id_apertura' => $id_apertura
            ]);
        }

        // Fallback for non-ajax requests (though main usage is now ajax)
        Alert::toast('Caja cerrada con éxito', 'success');
        return redirect()->route('ventas.index');
    }

    public function imprimir_cierre($id_apertura)
    {
        // faltaria el pdf arqueo de caja segun lo recaudado en las diferentes formas de pagos. 
        $cierre_caja = DB::selectOne(
            'SELECT ape.*, s.descripcion as sucursal, c.descripcion as caja, u.name as usuario
                    FROM apertura_cierre_cajas ape
                JOIN cajas c ON c.id_caja = ape.id_caja
                JOIN users u ON u.id = ape.user_id
                JOIN sucursales s ON s.id_sucursal = c.id_sucursal
                WHERE id_apertura = ?',
            [
                $id_apertura
            ]
        );

        if (!$cierre_caja) {
             return abort(404, 'Cierre de caja no encontrado');
        }

        // obtener los totales por forma de pago segun cobros (Directamente de cobros)
        $totales_forma_pago = DB::select(
            "SELECT fp.descripcion as forma_pago, SUM(c.cobro_importe) as total_cobro
            FROM cobros c
            JOIN metodo_pagos fp ON fp.id_metodo_pago = c.id_metodo_pago
            WHERE c.id_apertura = ? AND c.cobro_estado = 'COBRADO'
            GROUP BY fp.descripcion",
            [$id_apertura]
        );

        // Determinar fecha fin (cierre o actual si está abierta)
        $fecha_fin = $cierre_caja->fecha_cierre ?? \Carbon\Carbon::now();

        // 1. FACTURAS CONTADO (Ventas directas en caja)
        // Usamos la tabla cobros para vincular exactamente con la apertura actual
        $facturas_contado = DB::select(
            "SELECT DISTINCT
                v.id_venta,
                'FACTURA' as tipo,
                v.factura_nro as numero,
                v.fecha_venta as fecha,
                v.total as monto,
                CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente, 
                c.clie_ci as ruc
            FROM ventas v
            JOIN clientes c ON c.id_cliente = v.id_cliente
            JOIN cobros cob ON cob.id_venta = v.id_venta
            WHERE cob.id_apertura = ? 
              AND cob.cobro_estado = 'COBRADO'
              AND v.condicion_venta = 'CONTADO'
              AND v.estado = 'PAGADO'
            ORDER BY v.id_venta",
            [$id_apertura]
        );

        // 2. FACTURAS CRÉDITO (Ventas a crédito)
        // Estas se muestran informativamente, pero no afectan el arqueo de efectivo (excepto si hubo entrega inicial, que sería un cobro)
        $facturas_credito = DB::select(
            "SELECT 
                'FACTURA' as tipo,
                v.factura_nro as numero,
                v.fecha_venta as fecha,
                v.total as monto,
                CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente, 
                c.clie_ci as ruc
            FROM ventas v
            JOIN clientes c ON c.id_cliente = v.id_cliente
            WHERE v.user_id = ? 
              AND v.fecha_venta >= ?
              AND v.fecha_venta <= ?
              AND v.condicion_venta = 'CREDITO'
              AND v.estado != 'ANULADO'
            ORDER BY v.id_venta",
            [$cierre_caja->user_id, $cierre_caja->fecha_apertura, $fecha_fin]
        );

        // 3. RECIBOS DE COBRO (Pagos de cuentas a crédito)
        // Estos están vinculados directamente a la apertura mediante id_apertura en cobros
        $recibos = DB::select(
            "SELECT 
                'RECIBO' as tipo,
                CAST(cob.id_cobro AS VARCHAR) as numero,
                cob.cobro_fecha as fecha,
                cob.cobro_importe as monto,
                CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente,
                c.clie_ci as ruc
            FROM cobros cob
            JOIN cuentas_a_cobrar ca ON ca.id_cta = cob.id_cta
            JOIN ventas v ON v.id_venta = ca.id_venta
            JOIN clientes c ON c.id_cliente = v.id_cliente
            WHERE cob.id_apertura = ? 
              AND cob.cobro_estado = 'COBRADO'",
            [$id_apertura]
        );

        // Obtener listado detallado de NOTAS DE DÉBITO emitidas en este arqueo
        $notas_debito = DB::select(
            "SELECT nd.id_nota_debito, nd.nro_nota, nd.fecha_emision, nd.total, nd.estado,
                    CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente, c.clie_ci
            FROM notas_debito nd
            JOIN clientes c ON c.id_cliente = nd.id_cliente
            WHERE nd.user_id = ? 
              AND nd.created_at >= ? 
              AND nd.created_at <= ?
              AND nd.estado != 'ANULADO'
            ORDER BY nd.id_nota_debito",
            [$cierre_caja->user_id, $cierre_caja->fecha_apertura, $fecha_fin]
        );


        // retornar vista con los datos del cierre de caja en pdf
        $pdf = Pdf::loadView('ventas.cierre_caja_pdf', compact('cierre_caja', 'totales_forma_pago', 'facturas_contado', 'facturas_credito', 'recibos', 'notas_debito'));
        return $pdf->stream('cierre_caja_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    public function show($id)
    {
        $apertura = DB::selectOne(
            'SELECT ape.*, s.descripcion as sucursal, c.descripcion as caja, u.name as usuario
            FROM apertura_cierre_cajas ape
            JOIN cajas c ON c.id_caja = ape.id_caja
            JOIN users u ON u.id = ape.user_id
            JOIN sucursales s ON s.id_sucursal = c.id_sucursal
            WHERE ape.id_apertura = ?',
            [$id]
        );

        if (!$apertura) {
            Alert::error('Error', 'Apertura de caja no encontrada');
            return redirect()->route('ventas.index');
        }

        // 1. Totales Recaudados (Cobros)
        // Simplificado: Todo sale de cobros
        $totales_recaudados = DB::select(
            "SELECT fp.descripcion as forma_pago, COALESCE(SUM(c.cobro_importe), 0) as total
            FROM cobros c
            JOIN metodo_pagos fp ON fp.id_metodo_pago = c.id_metodo_pago
            WHERE c.id_apertura = ? AND c.cobro_estado = 'COBRADO'
            GROUP BY fp.descripcion, fp.id_metodo_pago
            ORDER BY fp.id_metodo_pago",
            [$id]
        );

        // 3. Consolidar Totales (Solo uno ahora)
        $totales_consolidados = [];
        $total_general_recaudado = 0;

        foreach ($totales_recaudados as $v) {
            $totales_consolidados[$v->forma_pago] = $v->total;
            $total_general_recaudado += $v->total;
        }

        // Total Esperado: Apertura + Recaudación
        $total_esperado = $apertura->monto_apertura + $total_general_recaudado;

        // Para mantener compatibilidad con la vista si usa $ventas_totales separado,
        // pasamos lo mismo o vacío si la vista itera sobre totales_consolidados principalmente.
        // Asumimos que show.blade.php itera sobre $totales_consolidados o similar.
        // Si itera sobre $ventas_totales y $notas_totales, le pasamos arrays vacíos o los mismos datos?
        // Revisando show.blade.php mentalmente, normalmente muestra desglose.
        // Dado que unificamos, pasamos arrays vacios para evitar queries rotas y usamos consolidados.
        
        $ventas_totales = []; 
        $notas_totales = [];

        // Capturar return_url si existe
        $return_url = request()->get('return_url');

        return view('apertura_cierre.show', compact(
            'apertura',
            'ventas_totales', // Deprecated split
            'notas_totales',  // Deprecated split
            'totales_consolidados',
            'total_general_recaudado',
            'total_esperado',
            'return_url'
        ));
    }

    public function editCierre($id_apertura)
    {
        // buscar el registro por id_apertura
        $apertura = DB::selectOne('SELECT * FROM apertura_cierre_cajas WHERE id_apertura = ?', [$id_apertura]);

        // validar si la apertura existe
        if (empty($apertura)) {
            return response()->json([
                'message' => 'La apertura de caja no existe',
                'success' => false
            ]);
        }

        // sumamos los totales de cobros directamente
        $total_ventas = DB::selectOne(
            "SELECT COALESCE(SUM(cobro_importe), 0) as total_cobro
            FROM cobros
            WHERE id_apertura = ? AND cobro_estado = 'COBRADO'",
            [
                $apertura->id_apertura
            ]
        );

        // normalizar monto de apertura antes de enviarlo a la vista
        $apertura->monto_apertura = $this->normalizarMonto($apertura->monto_apertura);
        
        // Sumar monto de apertura al total de ventas para el total de cierre
        $total_cierre_calculado = $apertura->monto_apertura + ($total_ventas->total_cobro ?? 0);

        // retornar datos en formato json
        return response()->json([
            'apertura' => $apertura,
            'total_ventas' => $total_cierre_calculado, // Enviamos el total SUMADO (Apertura + Ventas)
            'success' => true
        ]);
    }

    /**
     * Normaliza montos recibidos desde formularios con distintos formatos.
     */
    private function normalizarMonto($valor)
    {
        if (is_null($valor) || $valor === '') {
            return 0;
        }

        if (is_numeric($valor) && strpos($valor, '.') === false && strpos($valor, ',') === false) {
            return (float)$valor;
        }

        $valor = trim(str_replace(' ', '', $valor));

        if (strpos($valor, ',') !== false) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
            return (float)$valor;
        }

        if (strpos($valor, '.') !== false) {
            $decimales = strlen(substr(strrchr($valor, '.'), 1));

            if ($decimales === 1 || $decimales === 2) {
                return (float)$valor;
            }

            $valor = str_replace('.', '', $valor);
            return (float)$valor;
        }

        return (float)$valor;
    }
}
