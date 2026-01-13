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

        // validar que no exista montos sin cobrar en las ventas
        $ventas_sin_cobrar = DB::selectOne(
            'SELECT COUNT(*) AS no_cobrados 
            FROM ventas 
            WHERE id_apertura = ? AND estado = ?',
            [
                $id_apertura,
                'PENDIENTE'
            ]
        );
        // si existen ventas sin cobrar no se puede cerrar caja
        if ($ventas_sin_cobrar->no_cobrados > 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cerrar la caja, existen ventas sin cobrar'
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

        // AGREGAR MOVIMIENTO DE CAJA: RECAUDACION A DEPOSITAR
        // Se registra el monto total de cierre como un movimiento de salida (a depositar)
        try {
            DB::insert('INSERT INTO movimientos_caja (id_apertura, monto, fecha_hora, id_tipo_transaccion, id_forma_pago, documento, cierre, id_usuario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                $id_apertura,
                $input['monto_cierre'],
                Carbon::now(),
                9, // 9: RECAUDACIONES A DEPOSITAR (segun migration created)
                1, // 1: EFECTIVO (asumido)
                'CIERRE DE CAJA',
                1, // cierre = 1
                auth()->user()->id
            ]);
        } catch (\Exception $e) {
            // Log error silently usually, or handle it. For now, continue as closure is main event.
            // \Log::error('Error creating movement on close: ' . $e->getMessage());
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

        // obtener los totales por forma de pago segun cobros
        // obtener los totales por forma de pago segun cobros (Ventas + Notas de Débito)
        $totales_forma_pago = DB::select(
            "SELECT forma_pago, SUM(total_cobro) as total_cobro
            FROM (
                SELECT fp.descripcion as forma_pago, c.cobro_importe as total_cobro
                FROM cobros c
                JOIN metodo_pagos fp ON fp.id_metodo_pago = c.id_metodo_pago
                JOIN ventas ve on ve.id_venta = c.id_venta 
                WHERE ve.id_apertura = ? AND ve.estado = 'PAGADO'

                UNION ALL

                SELECT fp.descripcion as forma_pago, c.cobro_importe as total_cobro
                FROM cobros c
                JOIN metodo_pagos fp ON fp.id_metodo_pago = c.id_metodo_pago
                JOIN notas_debito nd on nd.id_nota_debito = c.id_nota_debito
                WHERE nd.id_apertura = ? 
                  AND nd.estado != 'ANULADO'
            ) as combined
            GROUP BY forma_pago",
            [
                $id_apertura,
                $id_apertura
            ]
        );

        // Obtener listado detallado de FACTURAS emitidas en este arqueo
        $facturas = DB::select(
            "SELECT v.id_venta, v.factura_nro, v.fecha_venta, v.total, v.estado,
                    CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente, c.clie_ci
            FROM ventas v
            JOIN clientes c ON c.id_cliente = v.id_cliente
            WHERE v.id_apertura = ? AND v.estado = 'PAGADO'
            ORDER BY v.id_venta",
            [$id_apertura]
        );

        // Obtener listado detallado de NOTAS DE DÉBITO emitidas en este arqueo
        $notas_debito = DB::select(
            "SELECT nd.id_nota_debito, nd.nro_nota, nd.fecha_emision, nd.total, nd.estado,
                    CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente, c.clie_ci
            FROM notas_debito nd
            JOIN clientes c ON c.id_cliente = nd.id_cliente
            WHERE nd.id_apertura = ? AND nd.estado != 'ANULADO'
            ORDER BY nd.id_nota_debito",
            [$id_apertura]
        );

        // retornar vista con los datos del cierre de caja en pdf
        $pdf = Pdf::loadView('ventas.cierre_caja_pdf', compact('cierre_caja', 'totales_forma_pago', 'facturas', 'notas_debito'));
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

        // 1. Totales de Ventas (Agrupado por forma de pago)
        $ventas_totales = DB::select(
            "SELECT fp.descripcion as forma_pago, COALESCE(SUM(c.cobro_importe), 0) as total
            FROM cobros c
            JOIN metodo_pagos fp ON fp.id_metodo_pago = c.id_metodo_pago
            JOIN ventas ve ON ve.id_venta = c.id_venta
            WHERE ve.id_apertura = ? AND ve.estado = 'PAGADO'
            GROUP BY fp.descripcion, fp.id_metodo_pago
            ORDER BY fp.id_metodo_pago",
            [$id]
        );

        // 2. Totales de Notas de Débito (Agrupado por forma de pago) - Filtrado por fecha/usuario
        // Nota: Las notas de débito no tienen id_apertura, usamos fecha y usuario.
        $notas_totales = DB::select(
            "SELECT fp.descripcion as forma_pago, COALESCE(SUM(c.cobro_importe), 0) as total
            FROM cobros c
            JOIN metodo_pagos fp ON fp.id_metodo_pago = c.id_metodo_pago
            JOIN notas_debito nd ON nd.id_nota_debito = c.id_nota_debito
            WHERE nd.user_id = ? 
              AND nd.fecha_emision = ? 
              AND nd.estado != 'ANULADO'
            GROUP BY fp.descripcion, fp.id_metodo_pago
            ORDER BY fp.id_metodo_pago",
            [$apertura->user_id, $apertura->fecha_apertura]
        );

        // 3. Consolidar Totales (Ventas + Notas)
        $totales_consolidados = [];
        $total_general_recaudado = 0;

        foreach ($ventas_totales as $v) {
            if (!isset($totales_consolidados[$v->forma_pago])) {
                $totales_consolidados[$v->forma_pago] = 0;
            }
            $totales_consolidados[$v->forma_pago] += $v->total;
            $total_general_recaudado += $v->total;
        }

        foreach ($notas_totales as $n) {
            if (!isset($totales_consolidados[$n->forma_pago])) {
                $totales_consolidados[$n->forma_pago] = 0;
            }
            $totales_consolidados[$n->forma_pago] += $n->total;
            $total_general_recaudado += $n->total;
        }

        // Total Esperado: Apertura + Recaudación
        $total_esperado = $apertura->monto_apertura + $total_general_recaudado;

        // Capturar return_url si existe
        $return_url = request()->get('return_url');

        return view('apertura_cierre.show', compact(
            'apertura',
            'ventas_totales', // Solo ventas para desglose si se quiere
            'notas_totales',  // Solo notas para desglose
            'totales_consolidados', // Total real por medio de pago
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

        // sumamos los totales de las ventas y notas de débito realizadas en la caja abierta
        $total_ventas = DB::selectOne(
            "SELECT SUM(total_cobro) as total_cobro
            FROM (
                SELECT coalesce(sum(c.cobro_importe), 0) as total_cobro
                FROM cobros c
                JOIN ventas ve on ve.id_venta = c.id_venta 
                WHERE ve.id_apertura = ? and ve.estado = 'PAGADO'

                UNION ALL

                SELECT coalesce(sum(c.cobro_importe), 0) as total_cobro
                FROM cobros c
                JOIN notas_debito nd on nd.id_nota_debito = c.id_nota_debito
                WHERE nd.user_id = ? 
                  AND nd.fecha_emision = ? 
                  AND nd.estado != 'ANULADO'
            ) as combined",
            [
                $apertura->id_apertura,
                $apertura->user_id,
                $apertura->fecha_apertura
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
