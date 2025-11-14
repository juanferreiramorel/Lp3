<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use RealRashid\SweetAlert\Facades\Alert;
use Luecano\NumeroALetras\NumeroALetras;
use App\Models\Timbrado;
use App\Models\Empresa;

use function Laravel\Prompts\error;

class VentaController extends AppBaseController
{
    public function __construct()
    {
        // validar que el usuario este autenticado
        $this->middleware('auth');
        // validar permisos para cada accion
        $this->middleware('permission:ventas index')->only(['index', 'show', 'imprimir-factura', 'factura']);
        $this->middleware('permission:ventas create')->only(['create', 'store', 'buscarProducto', 'crearDesdePedido']);
        $this->middleware('permission:ventas edit')->only(['edit', 'update']);
        $this->middleware('permission:ventas destroy')->only(['destroy']);
    } 
    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            // Ambas fechas proporcionadas
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND v.fecha_venta BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            // Solo fecha desde (mayor o igual)
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND v.fecha_venta >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            // Solo fecha hasta (menor o igual)
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND v.fecha_venta <= '{$fecha_hasta}'";
        }

        // FILTRO POR CLIENTE
        if (!empty($input['id_cliente'])) {
            $sql_filtros .= " AND v.id_cliente = " . (int)$input['id_cliente'];
        }

        // FILTRO POR CONDICIÓN DE VENTA
        if (!empty($input['condicion_venta'])) {
            $sql_filtros .= " AND v.condicion_venta = '" . $input['condicion_venta'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND v.id_sucursal = " . (int)$input['id_sucursal'];
        }

        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $sql_busqueda = 'WHERE 1=1';

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql_busqueda = "WHERE v.id_venta = " . (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd
                $buscarFecha = $buscarTerm;
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }

                // Limpiar separadores de miles para búsqueda de montos
                $buscarMonto = str_replace('.', '', $buscarTerm);

                $sql_busqueda = "WHERE (CAST(v.id_venta AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(c.clie_nombre AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(c.clie_apellido AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(v.factura_nro AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(c.clie_ci AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(v.fecha_venta AS TEXT) ILIKE '%{$buscarFecha}%'
                    OR TO_CHAR(v.fecha_venta, 'DD/MM/YYYY') ILIKE '%{$buscarTerm}%'
                    OR v.condicion_venta ILIKE '%{$buscarTerm}%'
                    OR CAST(v.total AS TEXT) ILIKE '%{$buscarMonto}%'
                    OR TO_CHAR(v.total, 'FM999G999G999G999G999') ILIKE '%{$buscarTerm}%'
                    OR users.name ILIKE '%{$buscarTerm}%'
                    OR v.estado ILIKE '%{$buscarTerm}%')";
            }
        }

        $idsPermitidos = $this->getSucursalesPermitidasIds();
        $sqlSucursales = '';
        if (!empty($idsPermitidos)) {
            $sqlSucursales = ' AND v.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        }

        $ventas = DB::select(
            "SELECT v.*, concat(c.clie_nombre,' ', c.clie_apellido) as cliente, c.clie_ci,
            users.name as usuario
            FROM ventas v
                JOIN clientes c ON v.id_cliente = c.id_cliente
                JOIN users ON v.user_id = users.id
            {$sql_busqueda} {$sql_filtros} {$sqlSucursales}
            ORDER BY v.fecha_venta DESC"
        );

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($ventas);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($ventas, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $ventas = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // si la accion es buscardor entonces significa que se debe recargar mediante ajax la tabla
        if ($request->ajax()) {
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('ventas.table')->with('ventas', $ventas);
        }

        // Consulta para recuperar cajas
        $caja = DB::table('cajas')
            ->where('id_sucursal', auth()->user()->id_sucursal) // filtrar por sucursal del usuario
            ->pluck('descripcion', 'id_caja');

        // Validar que el usuario no tenga una caja abierta en la fecha actual
        $caja_abierta = DB::selectOne(
            "SELECT * FROM apertura_cierre_cajas
            WHERE user_id = ? AND estado = 'ABIERTA'",
            [auth()->user()->id]
        );

        // OBTENER DATOS PARA COMBOS DE FILTROS
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, concat(clie_nombre,' ', clie_apellido) as cliente")
            ->pluck('cliente', 'id_cliente');

        $sucursales = $this->getSucursalesPermitidas();

        $condiciones_venta = [
            'CONTADO' => 'CONTADO',
            'CREDITO' => 'CREDITO'
        ];

        return view('ventas.index')->with('ventas', $ventas)
            ->with('cajas', $caja)
            ->with('caja_abierta', $caja_abierta)
            ->with('clientes', $clientes)
            ->with('sucursales', $sucursales)
            ->with('condiciones_venta', $condiciones_venta);
    }

    public function create()
    {
        // Crear select para clientes utilizamos selectRaw para consultas puras
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, concat(clie_nombre,' ', clie_apellido) as cliente")
            ->pluck('cliente', 'id_cliente');

        // Condicion de venta opciones: CONTADO O CREDITO
        $condicion_venta = [
            'CONTADO' => 'CONTADO',
            'CREDITO' => 'CREDITO'
        ];

        // Intervalo de vencimiento
        $intervalo_vencimiento = [
            '7' => '7 Días',
            '15' => '15 Días',
            '30' => '30 Días'
        ];

        // Enviar datos de sucursales segun sucursal del usuario
        $usuarioActual = auth()->user();
        $sucursalesPermitidasIds = $this->getSucursalesPermitidasIds($usuarioActual);
        $sucursales = DB::table('sucursales')
            ->whereIn('id_sucursal', $sucursalesPermitidasIds)
            ->pluck('descripcion', 'id_sucursal');
        $usuario = $usuarioActual->name;

        // enviar datos de apertura de caja a ventas 
        $apertura_caja = DB::selectOne(
            "SELECT ap.id_apertura,
                ap.fecha_apertura,
                lpad('1', 3, '0')as establecimiento,
                lpad(cast(c.punto_expedicion as text), 3, '0')as punto_expedicion,
                lpad(cast(coalesce(max(c.ultima_factura_impresa), 0) + 1 as text), 7, '0') as nro_factura
            FROM apertura_cierre_cajas ap
                JOIN cajas c on c.id_caja = ap.id_caja 
            WHERE ap.user_id = ? and ap.estado = 'ABIERTA'
            GROUP BY
            ap.id_apertura,
            ap.fecha_apertura,
            c.punto_expedicion,
            c.ultima_factura_impresa",
            [auth()->user()->id]
        );

        // validar que no exista una caja abierta para el usuario en la fecha pasada
        if (
            !empty($apertura_caja)
            && Carbon::parse($apertura_caja->fecha_apertura)->format('Y-m-d')
            < Carbon::now()->format('Y-m-d')
        ) {
            Alert::toast('Debe cerrar la caja abierta para poder realizar una venta', 'error');
            // retornar al index de ventas
            return redirect()->route('ventas.index');
        }

        // Obtener timbrados vigentes de la empresa de la sucursal del usuario
        $timbrados_vigentes = [];
        if (!empty($apertura_caja)) {
            // Obtener la empresa de la sucursal del usuario
            $sucursal = DB::selectOne('SELECT id_empresa FROM sucursales WHERE id_sucursal = ?', [auth()->user()->id_sucursal]);
            if ($sucursal && $sucursal->id_empresa) {
                $timbrados_vigentes = Timbrado::where('id_empresa', $sucursal->id_empresa)
                    ->vigentes()
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->get();
            }
        }

        return view('ventas.create')->with('clientes', $clientes)
            ->with('usuario', $usuario)
            ->with('condicion_venta', $condicion_venta)
            ->with('intervalo_vencimiento', $intervalo_vencimiento)
            ->with('sucursales', $sucursales)
            ->with('apertura_caja', $apertura_caja)
            ->with('timbrados_vigentes', $timbrados_vigentes);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        // Para que el validator no falle al momento de validacion require_if
        $input['intervalo'] = $input['intervalo'] ?? 0;
        $input['cantidad_cuota'] = $input['cantidad_cuota'] ?? 0;
        // Validaciones personalizadas para el formulario ventas
        $validacion = Validator::make(
            $input,
            [
                'id_cliente' => 'required|exists:clientes,id_cliente',
                'condicion_venta' => 'required|in:CONTADO,CREDITO',
                'intervalo' => 'required_if:condicion_venta,CREDITO|in:0,7,15,30',
                'cantidad_cuota' => 'required_if:condicion_venta,CREDITO|integer',
                'fecha_venta' => 'required|date',
                'user_id' => 'required|exists:users,id',
                'id_apertura' => 'required|exists:apertura_cierre_cajas,id_apertura',
                'id_sucursal' => 'required|exists:sucursales,id_sucursal',
                'factura_nro' => 'nullable|string|max:15',
                'id_timbrado' => 'nullable|exists:timbrados,id_timbrado',
            ],
            [
                'id_cliente.required' => 'El campo cliente es obligatorio.',
                'id_cliente.exists' => 'El cliente seleccionado no es válido.',
                'condicion_venta.required' => 'El campo condición de venta es obligatorio.',
                'condicion_venta.in' => 'La condición de venta seleccionada no es válida.',
                'intervalo.required_if' => 'El campo intervalo es obligatorio cuando la condición de venta es crédito.',
                'intervalo.in' => 'El intervalo seleccionado no es válido.',
                'cantidad_cuota.required_if' => 'El campo cantidad de cuota es obligatorio cuando la condición de venta es crédito.',
                'cantidad_cuota.integer' => 'El campo cantidad de cuota debe ser un número entero.',
                'fecha_venta.required' => 'El campo fecha de venta es obligatorio.',
                'fecha_venta.date' => 'El campo fecha de venta debe ser una fecha válida.',
                'user_id.required' => 'El campo usuario es obligatorio.',
                'user_id.exists' => 'El usuario seleccionado no es válido.',
                'id_apertura.required' => 'El campo apertura es obligatorio.',
                'id_apertura.exists' => 'La apertura seleccionada no es válida.',
                'id_sucursal.required' => 'El campo sucursal es obligatorio.',
                'id_sucursal.exists' => 'La sucursal seleccionada no es válida.',
                'factura_nro.max' => 'El número de factura no debe exceder los 15 caracteres.',
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // validar fecha de venta no sea mayor a la fecha actual
        if (Carbon::parse($input['fecha_venta'])->format('Y-m-d') > Carbon::now()->format('Y-m-d')) {
            Alert::toast('La fecha de venta no puede ser mayor a la fecha actual', 'error');
            return redirect()->back()->withInput($input);
        }

        // Manejar timbrado si está presente
        $numero_factura_timbrado = null;
        $timbrado = null;
        if (!empty($input['id_timbrado'])) {
            $timbrado = Timbrado::find($input['id_timbrado']);

            if (!$timbrado) {
                Alert::toast('Timbrado no encontrado', 'error');
                return redirect()->back()->withInput($input);
            }

            if (!$timbrado->estaVigente()) {
                Alert::toast('El timbrado seleccionado no está vigente', 'error');
                return redirect()->back()->withInput($input);
            }

            // Generar número de factura con timbrado
            $numero_factura_timbrado = $timbrado->generarNumeroFactura();

            if (!$numero_factura_timbrado) {
                Alert::toast('No se pudo generar el número de factura. Timbrado agotado o vencido', 'error');
                return redirect()->back()->withInput($input);
            }

            // Verificar que el número de factura timbrado no exista
            $factura_exist = DB::selectOne('SELECT * FROM ventas WHERE numero_factura_timbrado = ?', [$numero_factura_timbrado]);
            if (!empty($factura_exist)) {
                Alert::toast('El número de factura timbrado ya existe. Por favor intente nuevamente.', 'error');
                return redirect()->back()->withInput($input);
            }

            // Usar el número de timbrado como número de factura principal
            $input['factura_nro'] = $numero_factura_timbrado;
        } else {
            // Si NO hay timbrado, validar que el número de factura manual sea único
            if (!empty($input['factura_nro'])) {
                $factura_exist = DB::selectOne('SELECT * FROM ventas WHERE factura_nro = ? AND id_timbrado IS NULL', [$input['factura_nro']]);
                if (!empty($factura_exist)) {
                    Alert::toast('El número de factura ya existe', 'error');
                    return redirect()->back()->withInput($input);
                }
            }
        }

        // Si la validación pasa, continuar con el almacenamiento de la venta
        // Recuperamos el usuario en session
        $user_id = auth()->user()->id;

        // Limpiar el formato del total (quitar separadores de miles)
        $total = isset($input['total']) ? str_replace('.', '', $input['total']) : 0;

        // Utilizamos insertGetId para obtener el ID de la venta registrada
        // Agregar transacciones
        DB::beginTransaction();
        try {
            $ventas = DB::table('ventas')->insertGetId([
                'id_cliente' => $input['id_cliente'],
                'condicion_venta' => $input['condicion_venta'],
                'intervalo' => $input['intervalo'] ?? 0,
                'cantidad_cuota' => $input['cantidad_cuota'] ?? 0,
                'fecha_venta' => $input['fecha_venta'],
                'factura_nro' => $input['factura_nro'] ?? '0',
                'numero_factura_timbrado' => $numero_factura_timbrado,
                'id_timbrado' => !empty($input['id_timbrado']) ? $input['id_timbrado'] : null,
                'user_id' => $user_id,
                'total' => $total,
                'id_sucursal' => $input['id_sucursal'],
                'estado' => 'COMPLETADO',
                'id_apertura' => $input['id_apertura'],
                'id_pedido' => !empty($input['id_pedido']) ? $input['id_pedido'] : null,
            ], 'id_venta');

            // insertar detalle ventas
            $subtotal = 0;
            // validar que existea $input['codigo'] de productos
            if ($request->has('codigo')) {
                foreach ($input['codigo'] as $key => $codigo) {
                    // quitar separador de miles
                    $monto = str_replace('.', '', $input['precio'][$key]);
                    // calculo de subtotal
                    $subtotal += $monto * $input['cantidad'][$key];
                    // insertar en detalle_ventas
                    DB::insert(
                        'INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio) VALUES (?, ?, ?, ?)',
                        [
                            $ventas,
                            $codigo,
                            $input['cantidad'][$key],
                            $monto
                        ]
                    );

                    // disminuir stocks segun producto y sucursal
                    DB::update('UPDATE stocks SET cantidad = cantidad - ? WHERE id_producto = ? AND id_sucursal = ?', [
                        $input['cantidad'][$key],
                        $codigo,
                        $input['id_sucursal']
                    ]);
                }
                // actualizar el total en la tabla ventas valor total
                DB::update('UPDATE ventas SET total = ? WHERE id_venta = ?', [
                    $subtotal,
                    $ventas
                ]);
            }

            // Incrementar uso del timbrado si se utilizó
            if ($timbrado) {
                $timbrado->incrementarUso();
            }

            // actualizar la ultima factura impresa en cajas si es que se envio un nro de factura
            if (!empty($input['factura_nro'])) {
                // extraer el nro de factura utilizando explode en la posicion 2
                // 001-002-0000001
                $factura_nro = explode('-', $input['factura_nro'])[2];
                // update de cajas y recuperar segun la apertura de caja el codigo de id_caja utilizando subconsulta
                DB::update('UPDATE cajas SET ultima_factura_impresa = ?
                WHERE id_caja = (SELECT id_caja FROM apertura_cierre_cajas WHERE id_apertura = ?)', [
                    (int) $factura_nro, // convertir a entero para quitar ceros a la izquierda
                    $input['id_apertura']
                ]);
            }

            // Si la venta se creó desde un pedido, actualizar el estado del pedido a PROCESADO
            if (!empty($input['id_pedido'])) {
                DB::update('UPDATE pedidos SET estado = ? WHERE id_pedido = ?', [
                    'PROCESADO',
                    $input['id_pedido']
                ]);
            }

            // Si todo esta bien realiza el envio e inserta la venta y detalle
            DB::commit();
        } catch (\Exception $e) {
            // si algo salio mal lo revertimos
            Log::info('Error al registrar la venta: ' . $e->getMessage());
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage())->withInput($request->all());
        }


        Alert::toast('Venta registrada exitosamente.', 'success');
        return redirect()->route('ventas.index');
    }

    public function show($id)
    {
        // Obtener la cabecera de ventas
        $venta = DB::selectOne(
            "SELECT v.*, concat(c.clie_nombre,' ', c.clie_apellido) as cliente, c.clie_ci,
            users.name as usuario
            FROM ventas v
                JOIN clientes c ON v.id_cliente = c.id_cliente
                JOIN users ON v.user_id = users.id
            WHERE v.id_venta = ?",
            [$id]
        );

        if (empty($venta)) {
            Alert::toast('Venta no encontrada', 'error');
            return redirect()->route('ventas.index');
        }

        // obtener los detalles de ventas
        $detalle_venta = DB::select(
            "SELECT dv.*, p.descripcion
            FROM detalle_ventas dv
                JOIN productos p ON dv.id_producto = p.id_producto
            WHERE dv.id_venta = ?",
            [$id]
        );

        return view('ventas.show')->with('ventas', $venta)->with('detalle_venta', $detalle_venta);
    }
    public function edit($id)
    {
        $venta = DB::selectOne('SELECT * FROM ventas WHERE id_venta = ?', [$id]);

        if (empty($venta)) {
            Flash::error('Venta no encontrada');
            return redirect()->route('ventas.index');
        }

        // Crear select para clientes utilizamos selectRaw para consultas puras
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, concat(clie_nombre,' ', clie_apellido) as cliente")
            ->pluck('cliente', 'id_cliente');

        // Compartir el usuario en session para el formulario ventas utilizando auth()
        $usuario = auth()->user()->name;

        // Condicion de venta opciones: CONTADO O CREDITO
        $condicion_venta = [
            'CONTADO' => 'CONTADO',
            'CREDITO' => 'CREDITO'
        ];

        // Intervalo de vencimiento
        $intervalo_vencimiento = [
            '7' => '7 Días',
            '15' => '15 Días',
            '30' => '30 Días'
        ];

        // Enviar datos de sucursales segun sucursal del usuario
        $sucursales = DB::table('sucursales')->where('id_sucursal', auth()->user()->id_sucursal)
            ->pluck('descripcion', 'id_sucursal');

        // obtener los detalles de ventas
        $detalle_venta = DB::select(
            "SELECT dv.*, p.descripcion
            FROM detalle_ventas dv
                JOIN productos p ON dv.id_producto = p.id_producto
            WHERE dv.id_venta = ?",
            [$id]
        );

        return view('ventas.edit')->with('ventas', $venta)
            ->with('detalle_venta', $detalle_venta)
            ->with('clientes', $clientes)
            ->with('condicion_venta', $condicion_venta)
            ->with('intervalo_vencimiento', $intervalo_vencimiento)
            ->with('usuario', $usuario)
            ->with('sucursales', $sucursales);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $ventas = DB::selectOne('SELECT * FROM ventas WHERE id_venta = ?', [$id]);

        if (empty($ventas)) {
            Flash::error('Venta no encontrada');
            return redirect()->route('ventas.index');
        }
        $input['intervalo'] = $input['intervalo'] ?? 0;
        $input['cantidad_cuota'] = $input['cantidad_cuota'] ?? 0;
        // Validaciones personalizadas para el formulario ventas
        $validacion = Validator::make(
            $input,
            [
                'condicion_venta' => 'required|in:CONTADO,CREDITO',
                'intervalo' => 'required_if:condicion_venta,CREDITO|in:0,7,15,30',
                'cantidad_cuota' => 'required_if:condicion_venta,CREDITO|integer',
                'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            ],
            [
                'condicion_venta.required' => 'El campo condición de venta es obligatorio.',
                'condicion_venta.in' => 'La condición de venta seleccionada no es válida.',
                'intervalo.required_if' => 'El campo intervalo es obligatorio cuando la condición de venta es crédito.',
                'intervalo.in' => 'El intervalo seleccionado no es válido.',
                'cantidad_cuota.required_if' => 'El campo cantidad de cuota es obligatorio cuando la condición de venta es crédito.',
                'cantidad_cuota.integer' => 'El campo cantidad de cuota debe ser un número entero.',
                'id_sucursal.required' => 'El campo sucursal es obligatorio.',
                'id_sucursal.exists' => 'La sucursal seleccionada no es válida.',
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        DB::beginTransaction();
        try {
            // Actualizar la venta
            DB::update('UPDATE ventas SET condicion_venta = ?, intervalo = ?, cantidad_cuota = ?, id_sucursal = ? WHERE id_venta = ?', [
                $input['condicion_venta'],
                $input['intervalo'],
                $input['cantidad_cuota'],
                $input['id_sucursal'],
                $id
            ]);

            // Actualizar los detalles de venta
            foreach ($input['codigo'] as $key => $codigo) {
                // quitar separador de miles
                $monto = str_replace('.', '', $input['precio'][$key]);
                // obtener detalle de venta si existe segun producto recibido
                $detalle = DB::selectOne('SELECT * FROM detalle_ventas WHERE id_venta = ? AND id_producto = ?', [$id, $codigo]);

                if (!empty($detalle)) {
                    // actualizar en detalle_ventas
                    DB::update(
                        'UPDATE detalle_ventas SET cantidad = ?, precio = ? WHERE id_venta = ? AND id_producto = ?',
                        [
                            $input['cantidad'][$key],
                            $monto,
                            $id,
                            $codigo
                        ]
                    );
                    // calcular diferencia de cantidad para disminucion de stock
                    $diferencia = $input['cantidad'][$key] - $detalle->cantidad;
                    // actualizar stocks segun producto y sucursal
                    if ($diferencia != 0) {
                        DB::update('UPDATE stocks SET cantidad = cantidad - ? WHERE id_producto = ? AND id_sucursal = ?', [
                            $diferencia,
                            $codigo,
                            $ventas->id_sucursal
                        ]);
                    }
                } else {
                    // Si no existe insertar nuevo detalle
                    DB::insert(
                        'INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio) VALUES (?, ?, ?, ?)',
                        [
                            $id,
                            $codigo,
                            $input['cantidad'][$key],
                            $monto
                        ]
                    );

                    // Disminuir stock
                    DB::update('UPDATE stocks SET cantidad = cantidad - ? WHERE id_producto = ? AND id_sucursal = ?', [
                        $input['cantidad'][$key],
                        $codigo,
                        $ventas->id_sucursal
                    ]);
                }
            }
            // Si todo esta bien realiza el envio e inserta la venta y detalle
            DB::commit();
        } catch (\Exception $e) {
            Log::info('Error al actualizar la venta: ' . $e->getMessage());
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }


        Alert::toast('Venta actualizada con exito.', 'success');
        return redirect()->route('ventas.index');
    }

    public function destroy($id)
    {
        $ventas = DB::selectOne('SELECT * FROM ventas WHERE id_venta = ?', [$id]);

        if (empty($ventas)) {
            Flash::error('Venta no encontrada');
            return redirect()->route('ventas.index');
        }

        // Usar transacciones 
        DB::beginTransaction();
        try {
            // Anular la venta
            DB::update(
                'UPDATE ventas SET estado = ? WHERE id_venta = ?',
                [
                    'ANULADO',
                    $id
                ]
            );

            // restaurar la cantidad de productos en stock para ello debemos consultar el detalle de venta para obtener datos del producto y cantidad
            $detalle_venta = DB::select('SELECT * FROM detalle_ventas WHERE id_venta = ?', [$id]);

            foreach ($detalle_venta as $item) {
                // actualizar las cantidades en stock segun la sucursal que obtendremos de la variable $ventas
                DB::update('UPDATE stocks SET cantidad = cantidad + ? WHERE id_producto = ? AND id_sucursal = ?', [
                    $item->cantidad,
                    $item->id_producto,
                    $ventas->id_sucursal
                ]);
            }
            // Si todo esta bien realiza el envio e inserta la venta y detalle
            DB::commit();
        } catch (\Exception $e) {
            Log::info('Error al anular la venta: ' . $e->getMessage());
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }

        Flash::success('Venta anulada exitosamente.');

        return redirect()->route('ventas.index');
    }

    public function buscarProducto(Request $request)
    {
        // Consulta personalizada para el buscador de productos con stock en la sucursal seleccionada segun parametro recibido
        $query   = $request->get('query'); //contenido a buscar
        $cod_suc = $request->get('cod_suc');

        ## Si query es vacio mostrar todo los productos utilizando un limitador
        if ($query) {
            $productos = DB::select(
                "SELECT productos.*, stocks.cantidad, stocks.id_sucursal
                    FROM productos
                        JOIN stocks ON productos.id_producto = stocks.id_producto 
                    WHERE (CAST(productos.id_producto AS TEXT) iLIKE ? OR CAST(productos.descripcion AS TEXT) iLIKE ?)
                        AND stocks.id_sucursal = ?
                    LIMIT 20",
                [
                    '%' . $query . '%',
                    '%' . $query . '%',
                    $cod_suc
                ]
            );
        } else {
            ## Cargar los primeros 20 productos si no hay búsqueda
            $productos = DB::select(
                "SELECT productos.*, stocks.cantidad, stocks.id_sucursal
                    FROM productos
                        JOIN stocks ON productos.id_producto = stocks.id_producto
                 WHERE stocks.id_sucursal = ?
                 LIMIT 20",
                [$cod_suc]
            );
        }

        ## Retornar la variable productos segun el filtro a nuestro html de buscar_productos
        return view('ventas.body_producto')->with('productos', $productos);
    }

    public function pdf()
    {
        $data = ['<h1>Prueba de PDF</h1>']; // Aquí debes preparar los datos que necesitas para la vista PDF
        $pdf = Pdf::loadView('ventas.invoice', compact('data'));
        return $pdf->download('invoice.pdf');
    }

    public function factura($id)
    {
        // recuperar datos de la venta con información de la empresa y timbrado
        $ventas = DB::selectOne(
            "SELECT v.*,
            concat(c.clie_nombre,' ', c.clie_apellido) as cliente,
            c.clie_ci,
            c.clie_direccion,
            c.clie_telefono,
            users.name as usuario,
            e.razon_social as empresa_razon_social,
            e.nombre_comercial as empresa_nombre,
            e.ruc as empresa_ruc,
            e.direccion as empresa_direccion,
            e.telefono as empresa_telefono,
            e.email as empresa_email,
            e.logo as empresa_logo,
            s.descripcion as sucursal_nombre,
            t.numero_timbrado,
            t.fecha_inicio as timbrado_fecha_inicio,
            t.fecha_vencimiento as timbrado_fecha_vencimiento
            FROM ventas v
                JOIN clientes c ON v.id_cliente = c.id_cliente
                JOIN users ON v.user_id = users.id
                JOIN apertura_cierre_cajas acc ON v.id_apertura = acc.id_apertura
                JOIN cajas cj ON acc.id_caja = cj.id_caja
                JOIN sucursales s ON cj.id_sucursal = s.id_sucursal
                LEFT JOIN empresas e ON s.id_empresa = e.id_empresa
                LEFT JOIN timbrados t ON v.id_timbrado = t.id_timbrado
            WHERE v.id_venta = ?",
            [$id]
        );

        // validacion de existencia
        if (empty($ventas)) {
            Alert::toast('Venta no encontrada', 'error');
            return redirect()->route('ventas.index');
        }

        // recuperar detalles de la venta
        $detalle_venta = DB::select(
            "SELECT dv.*, p.descripcion
            FROM detalle_ventas dv
                JOIN productos p ON dv.id_producto = p.id_producto
            WHERE dv.id_venta = ?",
            [$id]
        );

        // libreria para convertir numero a letras
        $formateo = new NumeroALetras();
        $numero_a_letras = $formateo->toWords($ventas->total);// recuperar total de la venta y convertir a letras

        // Determinar el número de factura a mostrar (priorizar timbrado)
        $numero_factura_display = $ventas->numero_factura_timbrado ?? $ventas->factura_nro;

        return view('ventas.factura')->with('ventas', $ventas)
            ->with('detalle_venta', $detalle_venta)
            ->with('numero_a_letras', $numero_a_letras)
            ->with('numero_factura_display', $numero_factura_display);
    }

    /** CREAR VENTA DESDE PEDIDO */
    public function crearDesdePedido($id)
    {
        try {
            // Obtener el pedido
            $pedido = DB::selectOne('SELECT * FROM pedidos WHERE id_pedido = ?', [$id]);

            if (empty($pedido)) {
                Alert::error('Error', 'Pedido no encontrado');
                return redirect()->route('ventas.index');
            }

            // Validar que el pedido esté PENDIENTE
            if ($pedido->estado != 'PENDIENTE') {
                Alert::warning('Advertencia', 'Solo se pueden procesar pedidos en estado PENDIENTE');
                return redirect()->route('ventas.index');
            }

            // Obtener los detalles del pedido y mapearlos al formato que espera la vista de ventas
            $detalle_venta = DB::select(
                "SELECT d.id_producto, d.cantidad, d.precio_unitario as precio, p.descripcion
                 FROM detalle_pedido d
                 JOIN productos p ON d.id_producto = p.id_producto
                 WHERE d.id_pedido = ?",
                [$id]
            );

            // Obtener datos necesarios para la vista de ventas
            $clientes = DB::table('clientes')
                ->selectRaw("id_cliente, CONCAT(clie_nombre,' ', clie_apellido) AS cliente")
                ->pluck('cliente', 'id_cliente');

            $sucursales = $this->getSucursalesPermitidas();

            $usuario = auth()->user()->name ?? '';

            $condicion_venta = [
                'CONTADO' => 'CONTADO',
                'CREDITO' => 'CREDITO'
            ];

            // Intervalo de vencimiento
            $intervalo_vencimiento = [
                '7'  => '7 Días',
                '15' => '15 Días',
                '30' => '30 Días'
            ];

            $cantidad_cuotas = [];
            for ($i = 1; $i <= 12; $i++) {
                $cantidad_cuotas[(string)$i] = (string)$i;
            }

            // Enviar datos de apertura de caja a ventas
            $apertura_caja = DB::selectOne(
                "SELECT ap.id_apertura,
                    ap.fecha_apertura,
                    lpad('1', 3, '0')as establecimiento,
                    lpad(cast(c.punto_expedicion as text), 3, '0')as punto_expedicion,
                    lpad(cast(coalesce(max(c.ultima_factura_impresa), 0) + 1 as text), 7, '0') as nro_factura,
                    c.id_caja
                FROM apertura_cierre_cajas ap
                JOIN cajas c ON c.id_caja = ap.id_caja
                WHERE ap.user_id = ? AND ap.estado = 'ABIERTA'
                GROUP BY
                    ap.id_apertura,
                    ap.fecha_apertura,
                    c.punto_expedicion,
                    c.ultima_factura_impresa,
                    c.id_caja",
                [auth()->user()->id]
            );

            // Crear un objeto venta simulado con los datos del pedido para pre-llenar el formulario
            $venta = new \stdClass();
            $venta->id_cliente = $pedido->id_cliente;
            $venta->id_sucursal = $pedido->id_sucursal;
            $venta->total = $pedido->total_pedido;

            // Pasar el pedido y sus detalles a la vista de crear venta
            return view('ventas.create', compact(
                'pedido',
                'detalle_venta',
                'venta',
                'clientes',
                'sucursales',
                'usuario',
                'condicion_venta',
                'intervalo_vencimiento',
                'cantidad_cuotas',
                'apertura_caja'
            ))->with('desde_pedido', true);

        } catch (\Exception $e) {
            Log::error('Error al crear venta desde pedido: ' . $e->getMessage());
            Alert::error('Error', 'Error al procesar el pedido: ' . $e->getMessage());
            return redirect()->route('ventas.index');
        }
    }
}
