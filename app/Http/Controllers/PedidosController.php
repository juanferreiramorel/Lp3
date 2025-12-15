<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\PedidosExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\AiOcrService;

class PedidosController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedidos index')->only(['index', 'show', 'pdf', 'getPendientes']);
        $this->middleware('permission:pedidos create')->only(['create', 'store', 'buscarProducto', 'createWithAi', 'processAiPdf', 'reviewAi', 'storeAi']);
        $this->middleware('permission:pedidos edit')->only(['edit', 'update']);
        $this->middleware('permission:pedidos destroy')->only(['destroy']);
        // El método storeClienteAjax requiere permiso de clientes create (o pedidos create como alternativa)
        $this->middleware('permission:clientes create|pedidos create')->only(['storeClienteAjax']);
    }

    /** LISTADO */
    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';
        $params = [];

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND p.fecha_pedido BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND p.fecha_pedido >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND p.fecha_pedido <= '{$fecha_hasta}'";
        }

        // FILTRO POR CLIENTE
        if (!empty($input['id_cliente'])) {
            $sql_filtros .= " AND p.id_cliente = " . (int)$input['id_cliente'];
        }

        // FILTRO POR ESTADO
        if (!empty($input['estado'])) {
            $sql_filtros .= " AND p.estado = '" . $input['estado'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND p.id_sucursal = " . (int)$input['id_sucursal'];
        }

        // BÚSQUEDA POR TEXTO GENERAL
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql_filtros .= " AND p.id_pedido = " . (int)$searchConfig['term'];
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

                $sql_filtros .= " AND (
                    cast(p.id_pedido as text) ILIKE '%{$buscarTerm}%'
                    OR c.clie_ci ILIKE '%{$buscarTerm}%'
                    OR CONCAT(c.clie_nombre,' ', c.clie_apellido) ILIKE '%{$buscarTerm}%'
                    OR cast(p.fecha_pedido as text) ILIKE '%{$buscarFecha}%'
                    OR TO_CHAR(p.fecha_pedido, 'DD/MM/YYYY') ILIKE '%{$buscarTerm}%'
                    OR cast(p.total_pedido as text) ILIKE '%{$buscarMonto}%'
                    OR TO_CHAR(p.total_pedido, 'FM999G999G999G999G999') ILIKE '%{$buscarTerm}%'
                    OR u.name ILIKE '%{$buscarTerm}%'
                    OR p.estado ILIKE '%{$buscarTerm}%'
                )";
            }
        }

        // Construir WHERE
        $where = "WHERE 1=1" . $sql_filtros;

        $idsPermitidos = $this->getSucursalesPermitidasIds();
        if (!empty($idsPermitidos)) {
            $where .= ' AND p.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        }

        // Exportar a Excel si se solicita
        if (isset($input['exportar']) && $input['exportar'] == 'excel') {
            $filtros = [
                'fecha_desde' => $input['fecha_desde'] ?? null,
                'fecha_hasta' => $input['fecha_hasta'] ?? null,
                'id_cliente' => $input['id_cliente'] ?? null,
                'estado' => $input['estado'] ?? null,
                'id_sucursal' => $input['id_sucursal'] ?? null,
            ];
            return Excel::download(new PedidosExport($filtros, $buscar, $idsPermitidos), 'pedidos.xlsx');
        }

        // Query base
        $queryBase = "FROM pedidos p
                      JOIN clientes c ON p.id_cliente = c.id_cliente
                      JOIN users u ON p.id_usuario = u.id";

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$where}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Paginación
        $page = $request->input('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Obtener registros paginados
        $query = "SELECT p.*, CONCAT(c.clie_nombre,' ', c.clie_apellido) AS cliente, c.clie_ci,
                         u.name AS usuario
                  {$queryBase} {$where}
                  ORDER BY p.fecha_pedido DESC
                  LIMIT {$perPage} OFFSET {$offset}";

        $pedidos = DB::select($query);

        // Crear paginador manual
        $pedidos = new LengthAwarePaginator(
            $pedidos,
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('pedidos.table')->with('pedidos', $pedidos);
        }

        // Obtener datos para los filtros
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre,' ', clie_apellido) AS cliente")
            ->pluck('cliente', 'id_cliente');
        $sucursales = $this->getSucursalesPermitidas();
        $estados = [
            'PENDIENTE' => 'Pendiente',
            'PROCESADO' => 'Procesado',
            'CANCELADO' => 'Cancelado'
        ];

        return view('pedidos.index', compact('pedidos', 'clientes', 'sucursales', 'estados'));
    }

    /** CREAR */
    public function create()
    {
        // Select de clientes (mismo estilo que ventas)
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre,' ', clie_apellido) AS cliente")
            ->pluck('cliente', 'id_cliente');

        // Sucursales (si existe la tabla)
        $sucursales = $this->getSucursalesPermitidas();

        // Usuario en sesión (solo para mostrar en la vista, el ID se guardará en store())
        $usuario = auth()->user()->name ?? '';

        // Estados para pedidos
        $estado = [
            'PENDIENTE' => 'PENDIENTE',
            'PROCESADO' => 'PROCESADO',
            'CANCELADO' => 'CANCELADO',
        ];

        return view('pedidos.create')->with(compact('clientes', 'sucursales', 'usuario', 'estado'));
    }

    /** GUARDAR */
    public function store(Request $request)
    {
        $input = $request->all();

        // Validaciones adaptadas al esquema de pedidos
        $validacion = Validator::make(
            $input,
            [
                'id_cliente'   => 'required|exists:clientes,id_cliente',
                'fecha_pedido' => 'required|date',
                'id_sucursal'  => 'nullable|exists:sucursales,id_sucursal',
                'estado'       => 'nullable|in:PENDIENTE,PROCESADO,CANCELADO',

                // Detalle (igual a ventas: codigo[], cantidad[], precio[])
                'codigo'       => 'required|array|min:1',
                'codigo.*'     => 'required|integer',
                'cantidad'     => 'required|array|min:1',
                'cantidad.*'   => 'required|numeric|min:1',
                'precio'       => 'required|array|min:1',
                'precio.*'     => 'required'
            ],
            [
                'codigo.required' => 'Debe agregar al menos un producto al pedido',
                'codigo.min' => 'Debe agregar al menos un producto al pedido',
                'cantidad.required' => 'Debe agregar al menos una cantidad al pedido',
                'cantidad.min' => 'Debe agregar al menos una cantidad al pedido',
                'precio.required' => 'Debe agregar al menos un precio al pedido',
                'precio.min' => 'Debe agregar al menos un precio al pedido',
                'precio.numeric' => 'El precio debe ser un número',
                'cantidad.numeric' => 'La cantidad debe ser un número',
                'cantidad.min' => 'La cantidad debe ser mayor o igual a 0',
                'precio.min' => 'El precio debe ser mayor o igual a 0'
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Usuario de la sesión que guardaremos en p.id_usuario
        $user_id = auth()->id();

        DB::beginTransaction();
        try {
            // Calcular total desde el detalle
            $total = 0;
            foreach ($input['codigo'] as $i => $codigo) {
                // normalizar "1.234" -> "1234"
                $monto = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);
                $total += ($cantidad * (float)$monto);
            }

            // Insertar cabecera de pedido (sin totales de IVA aún)
            $idPedido = DB::table('pedidos')->insertGetId([
                'id_cliente'   => $input['id_cliente'],
                'fecha_pedido' => $input['fecha_pedido'],
                'total_pedido' => $total,
                'id_sucursal'  => $input['id_sucursal'] ?? null,
                'estado'       => $input['estado'] ?? 'PENDIENTE',
                'id_usuario'   => $user_id,
            ], 'id_pedido');

            // Obtener información de impuestos de los productos
            $codigos = array_map('intval', $input['codigo']);
            $productosInfo = DB::table('productos as p')
                ->leftJoin('taxes as t', 'p.id_tax', '=', 't.id_tax')
                ->whereIn('p.id_producto', $codigos)
                ->select('p.id_producto', 'p.id_tax', 't.porcentaje')
                ->get()
                ->keyBy('id_producto')
                ->toArray();

            // Inicializar totales de impuestos
            $total_exento = 0;
            $total_gravado_5 = 0;
            $total_gravado_10 = 0;
            $total_iva_5 = 0;
            $total_iva_10 = 0;

            // Insertar detalle del pedido con cálculo de impuestos
            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);

                // Obtener info del producto
                $productoInfo = $productosInfo[(int)$codigo] ?? null;
                $porcentaje_impuesto = $productoInfo->porcentaje ?? 0;
                $id_tax = $productoInfo->id_tax ?? null;

                // Calcular precio sin impuesto (Fórmula Paraguaya)
                $precio_sin_impuesto = $porcentaje_impuesto > 0
                    ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                    : $precio_con_iva;

                $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                // Calcular subtotales por item
                $gravada_item = $precio_sin_impuesto * $cantidad;
                $impuesto_total_item = $monto_impuesto * $cantidad;

                // Acumular totales según tipo de impuesto
                if ($porcentaje_impuesto == 0) {
                    $total_exento += $gravada_item;
                } elseif ($porcentaje_impuesto == 5) {
                    $total_gravado_5 += $gravada_item;
                    $total_iva_5 += $impuesto_total_item;
                } elseif ($porcentaje_impuesto == 10) {
                    $total_gravado_10 += $gravada_item;
                    $total_iva_10 += $impuesto_total_item;
                }

                // Insertar detalle con campos de impuesto
                DB::table('detalle_pedido')->insert([
                    'id_pedido'           => $idPedido,
                    'id_producto'         => (int)$codigo,
                    'cantidad'            => $cantidad,
                    'precio_unitario'     => (float)$precio_con_iva,
                    'id_tax'              => $id_tax,
                    'precio_sin_impuesto' => (float)$gravada_item,
                    'monto_impuesto'      => (float)$impuesto_total_item,
                ]);
            }

            // Actualizar totales de impuestos en el pedido
            $total_iva = $total_iva_5 + $total_iva_10;
            DB::update(
                'UPDATE pedidos SET total_exento = ?, total_gravado_5 = ?, total_gravado_10 = ?,
                 total_iva_5 = ?, total_iva_10 = ?, total_iva = ? WHERE id_pedido = ?',
                [
                    $total_exento,
                    $total_gravado_5,
                    $total_gravado_10,
                    $total_iva_5,
                    $total_iva_10,
                    $total_iva,
                    $idPedido
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            Log::info('Error al registrar el pedido: ' . $e->getMessage());
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }

        Alert::success('Éxito', 'Pedido registrado exitosamente.');
        return redirect()->route('pedidos.index');
    }

    /** VER */
    public function show($id)
    {
        $pedido = DB::selectOne(
            "SELECT p.*, CONCAT(c.clie_nombre,' ', c.clie_apellido) AS cliente, c.clie_ci,
                    u.name AS usuario
               FROM pedidos p
               JOIN clientes c ON p.id_cliente = c.id_cliente
               JOIN users u   ON p.id_usuario = u.id
              WHERE p.id_pedido = ?",
            [$id]
        );

        if (empty($pedido)) {
            Alert::error('Error', 'Pedido no encontrado');
            return redirect()->route('pedidos.index');
        }

        // Detalle + productos con información de impuestos
        $detalle = DB::select(
            "SELECT d.*, pr.descripcion, t.nombre as tax_nombre, t.porcentaje as tax_porcentaje
               FROM detalle_pedido d
               JOIN productos pr ON pr.id_producto = d.id_producto
               LEFT JOIN taxes t ON d.id_tax = t.id_tax
              WHERE d.id_pedido = ?",
            [$id]
        );

        return view('pedidos.show')
            ->with('pedido', $pedido)
            ->with('detalle_pedido', $detalle);
    }

    /** EDITAR */
    public function edit($id)
    {
        $pedido = DB::selectOne('SELECT * FROM pedidos WHERE id_pedido = ?', [$id]);

        if (empty($pedido)) {
            Alert::error('Error', 'Pedido no encontrado');
            return redirect()->route('pedidos.index');
        }

        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre,' ', clie_apellido) AS cliente")
            ->pluck('cliente', 'id_cliente');

        $sucursales = $this->getSucursalesPermitidas();

        $usuario = auth()->user()->name ?? '';

        $detalle = DB::select(
            "SELECT d.*, pr.descripcion, t.porcentaje as tax_porcentaje
               FROM detalle_pedido d
               JOIN productos pr ON pr.id_producto = d.id_producto
               LEFT JOIN taxes t ON d.id_tax = t.id_tax
              WHERE d.id_pedido = ?",
            [$id]
        );

        return view('pedidos.edit', compact('pedido', 'clientes', 'sucursales', 'usuario'))
            ->with('detalle_pedido', $detalle);
    }

    /** ACTUALIZAR */
    public function update(Request $request, $id)
    {
        $input = $request->all();

        $pedido = DB::selectOne('SELECT * FROM pedidos WHERE id_pedido = ?', [$id]);

        if (empty($pedido)) {
            Alert::error('Error', 'Pedido no encontrado');
            return redirect()->route('pedidos.index');
        }

        // Validar que el pedido no esté PROCESADO o CANCELADO para permitir edición
        if ($pedido->estado == 'PROCESADO' || $pedido->estado == 'CANCELADO') {
            Alert::warning('Advertencia', 'No se puede modificar un pedido que ya está ' . $pedido->estado);
            return redirect()->route('pedidos.index');
        }

        $validacion = Validator::make(
            $input,
            [
                'fecha_pedido' => 'required|date',
                'id_sucursal'  => 'nullable|exists:sucursales,id_sucursal',
                'estado'       => 'nullable|in:PENDIENTE',

                'codigo'       => 'required|array|min:1',
                'codigo.*'     => 'required|integer',
                'cantidad'     => 'required|array|min:1',
                'cantidad.*'   => 'required|numeric|min:1',
                'precio'       => 'required|array|min:1',
                'precio.*'     => 'required'
            ],
            [
                'codigo.required' => 'Debe agregar al menos un producto al pedido',
                'codigo.min' => 'Debe agregar al menos un producto al pedido',
                'cantidad.required' => 'Debe agregar al menos una cantidad al pedido',
                'cantidad.min' => 'Debe agregar al menos una cantidad al pedido',
                'precio.required' => 'Debe agregar al menos un precio al pedido',
                'precio.min' => 'Debe agregar al menos un precio al pedido',
                'precio.numeric' => 'El precio debe ser un número',
                'cantidad.numeric' => 'La cantidad debe ser un número',
                'cantidad.min' => 'La cantidad debe ser mayor o igual a 0',
                'precio.min' => 'El precio debe ser mayor o igual a 0'
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        DB::beginTransaction();
        try {
            // Obtener todos los productos actuales del pedido
            $productosActuales = collect($input['codigo'] ?? []);

            // Obtener todos los detalles existentes del pedido
            $detallesExistentes = DB::select(
                'SELECT id_producto FROM detalle_pedido WHERE id_pedido = ?',
                [$id]
            );

            // Eliminar items que fueron removidos de la lista
            foreach ($detallesExistentes as $detalle) {
                if (!$productosActuales->contains($detalle->id_producto)) {
                    DB::delete(
                        'DELETE FROM detalle_pedido WHERE id_pedido = ? AND id_producto = ?',
                        [$id, $detalle->id_producto]
                    );
                }
            }

            // Obtener información de impuestos de los productos
            $codigos = array_map('intval', $input['codigo']);
            $productosInfo = DB::table('productos as p')
                ->leftJoin('taxes as t', 'p.id_tax', '=', 't.id_tax')
                ->whereIn('p.id_producto', $codigos)
                ->select('p.id_producto', 'p.id_tax', 't.porcentaje')
                ->get()
                ->keyBy('id_producto')
                ->toArray();

            // Inicializar totales
            $total = 0;
            $total_exento = 0;
            $total_gravado_5 = 0;
            $total_gravado_10 = 0;
            $total_iva_5 = 0;
            $total_iva_10 = 0;

            // Actualizar o insertar detalles con cálculo de impuestos
            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);

                // Obtener info del producto
                $productoInfo = $productosInfo[(int)$codigo] ?? null;
                $porcentaje_impuesto = $productoInfo->porcentaje ?? 0;
                $id_tax = $productoInfo->id_tax ?? null;

                // Calcular precio sin impuesto (Fórmula Paraguaya)
                $precio_sin_impuesto = $porcentaje_impuesto > 0
                    ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                    : $precio_con_iva;

                $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                // Calcular subtotales por item
                $gravada_item = $precio_sin_impuesto * $cantidad;
                $impuesto_total_item = $monto_impuesto * $cantidad;

                // Acumular totales
                $total += ($cantidad * (float)$precio_con_iva);

                if ($porcentaje_impuesto == 0) {
                    $total_exento += $gravada_item;
                } elseif ($porcentaje_impuesto == 5) {
                    $total_gravado_5 += $gravada_item;
                    $total_iva_5 += $impuesto_total_item;
                } elseif ($porcentaje_impuesto == 10) {
                    $total_gravado_10 += $gravada_item;
                    $total_iva_10 += $impuesto_total_item;
                }

                $detalle = DB::selectOne(
                    'SELECT * FROM detalle_pedido WHERE id_pedido = ? AND id_producto = ?',
                    [$id, (int)$codigo]
                );

                if (!empty($detalle)) {
                    // Actualizar detalle existente
                    DB::update(
                        'UPDATE detalle_pedido SET cantidad = ?, precio_unitario = ?, id_tax = ?,
                         precio_sin_impuesto = ?, monto_impuesto = ? WHERE id_pedido = ? AND id_producto = ?',
                        [
                            $cantidad,
                            (float)$precio_con_iva,
                            $id_tax,
                            (float)$gravada_item,
                            (float)$impuesto_total_item,
                            $id,
                            (int)$codigo
                        ]
                    );
                } else {
                    // Insertar nuevo detalle
                    DB::table('detalle_pedido')->insert([
                        'id_pedido'           => $id,
                        'id_producto'         => (int)$codigo,
                        'cantidad'            => $cantidad,
                        'precio_unitario'     => (float)$precio_con_iva,
                        'id_tax'              => $id_tax,
                        'precio_sin_impuesto' => (float)$gravada_item,
                        'monto_impuesto'      => (float)$impuesto_total_item,
                    ]);
                }
            }

            // Actualizar cabecera con totales de impuestos
            $total_iva = $total_iva_5 + $total_iva_10;
            DB::update(
                'UPDATE pedidos SET fecha_pedido = ?, total_pedido = ?, id_sucursal = ?, estado = ?,
                 total_exento = ?, total_gravado_5 = ?, total_gravado_10 = ?,
                 total_iva_5 = ?, total_iva_10 = ?, total_iva = ? WHERE id_pedido = ?',
                [
                    $input['fecha_pedido'],
                    $total,
                    $input['id_sucursal'] ?? null,
                    $input['estado'] ?? 'PENDIENTE',
                    $total_exento,
                    $total_gravado_5,
                    $total_gravado_10,
                    $total_iva_5,
                    $total_iva_10,
                    $total_iva,
                    $id
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            Log::info('Error al actualizar el pedido: ' . $e->getMessage());
            DB::rollback();
            Alert::error('Error', 'Error al actualizar el pedido: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        Alert::success('Éxito', 'Pedido actualizado exitosamente.');
        return redirect()->route('pedidos.index');
    }

    /** ELIMINAR / ANULAR */
    public function destroy($id)
    {
        $pedido = DB::selectOne('SELECT * FROM pedidos WHERE id_pedido = ?', [$id]);

        if (empty($pedido)) {
            Alert::error('Error', 'Pedido no encontrado.');
            return redirect()->route('pedidos.index');
        }

        DB::beginTransaction();
        try {
            // No borramos físico: marcamos estado CANCELADO
            DB::update('UPDATE pedidos SET estado = ? WHERE id_pedido = ?', ['CANCELADO', $id]);
            DB::commit();
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Log::info('Error al cancelar el pedido: ' . $e->getMessage());
            DB::rollback();
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->route('pedidos.index');
    }

    /** BUSCADOR DE PRODUCTO */
    public function buscarProducto(Request $request)
    {
        // Obtener parámetros - verificar tanto 'sucursal' como 'cod_suc' para compatibilidad
        $cod_suc = $request->get('sucursal') ?? $request->get('cod_suc');
        $buscar  = $request->get('q') ?? $request->get('query'); // texto de búsqueda

        if (!empty($buscar)) {
            $buscar = mb_strtolower($buscar, 'UTF-8');
            $productos = DB::select(
                "SELECT productos.*, stocks.cantidad, stocks.id_sucursal,
                        t.nombre as tax_nombre, COALESCE(t.porcentaje, 0) as tax_porcentaje_val
                   FROM productos
                   LEFT JOIN stocks ON productos.id_producto = stocks.id_producto 
                        AND stocks.id_sucursal = ?
                   LEFT JOIN taxes t ON productos.id_tax = t.id_tax
                  WHERE productos.activo = true
                  AND (LOWER(productos.descripcion) LIKE ? OR CAST(productos.id_producto AS TEXT) LIKE ?)
                  LIMIT 50",
                [$cod_suc, "%{$buscar}%", "%{$buscar}%"]
            );
        } else {
            // primeros 20 sin filtro
            $productos = DB::select(
                "SELECT productos.*, stocks.cantidad, stocks.id_sucursal,
                        t.nombre as tax_nombre, COALESCE(t.porcentaje, 0) as tax_porcentaje_val
                   FROM productos
                   LEFT JOIN stocks ON productos.id_producto = stocks.id_producto 
                        AND stocks.id_sucursal = ?
                   LEFT JOIN taxes t ON productos.id_tax = t.id_tax
                  WHERE productos.activo = true
                  LIMIT 20",
                [$cod_suc]
            );
        }

        // Procesar resultados para asegurar que tax_porcentaje sea correcto
        foreach ($productos as $index => $product) {
            // Convertir objeto a array para manipularlo más fácilmente
            $productArray = (array)$product;
            
            // Obtener el valor correcto de tax_porcentaje_val
            $tax_val = isset($productArray['tax_porcentaje_val']) ? $productArray['tax_porcentaje_val'] : 0;
            
            // Eliminar cualquier campo tax_porcentaje existente (por si acaso)
            unset($productArray['tax_porcentaje']);
            unset($productArray['tax_porcentaje_val']);
            
            // Validar y asignar el valor correcto
            if ((float)$tax_val > 100) {
                $productArray['tax_porcentaje'] = 0;
            } else {
                $productArray['tax_porcentaje'] = (float)$tax_val;
            }
            
            // Convertir de vuelta a objeto y reasignar al array
            $productos[$index] = (object)$productArray;
        }

        return view('pedidos.body_producto')->with('productos', $productos);
    }

    /** PDF */
    public function pdf()
    {
        $data = ['<h1>Pedido</h1>'];
        $pdf = Pdf::loadView('pedidos.invoice', compact('data'));
        return $pdf->download('pedido.pdf');
    }

    /** OBTENER PEDIDOS PENDIENTES PARA VENTA */
    public function getPendientes()
    {
        try {
            $pedidos = DB::select(
                "SELECT p.id_pedido,
                        p.fecha_pedido,
                        p.total_pedido,
                        p.estado,
                        CONCAT(c.clie_nombre, ' ', c.clie_apellido) AS cliente
                 FROM pedidos p
                 JOIN clientes c ON p.id_cliente = c.id_cliente
                 WHERE p.estado = 'PENDIENTE'
                 ORDER BY p.fecha_pedido DESC"
            );

            // Formatear los datos
            $pedidosFormateados = array_map(function($pedido) {
                return [
                    'id_pedido' => $pedido->id_pedido,
                    'cliente' => $pedido->cliente,
                    'fecha_pedido' => \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y'),
                    'total_pedido' => number_format($pedido->total_pedido, 0, ',', '.'),
                    'estado' => $pedido->estado
                ];
            }, $pedidos);

            return response()->json([
                'success' => true,
                'pedidos' => $pedidosFormateados
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /* ==================== MÉTODOS DE IA ==================== */

    /**
     * Mostrar formulario para crear pedido con IA
     */
    public function createWithAi()
    {
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre,' ', clie_apellido) AS cliente")
            ->pluck('cliente', 'id_cliente');
        $sucursales = $this->getSucursalesPermitidas();
        $usuario = auth()->user()->name;

        return view('pedidos.create_ai', compact('clientes', 'sucursales', 'usuario'));
    }

    /**
     * Procesar PDF subido con IA y redirigir a la vista de revisión
     */
    public function processAiPdf(Request $request, AiOcrService $aiService)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        try {
            // Guardar PDF temporalmente
            $file = $request->file('pdf_file');
            $tempPath = $file->store('temp', 'local');
            $fullPath = storage_path('app/' . $tempPath);

            // Procesar con IA
            $result = $aiService->processPurchaseOrderPdf($fullPath);

            // Limpiar archivo temporal
            Storage::disk('local')->delete($tempPath);

            if (!$result['success']) {
                Alert::error('Error', $result['error']);
                return redirect()->back();
            }

            $aiData = $result['data'];

            if (!$aiService->validateResponse($aiData)) {
                Alert::error('Error', 'Respuesta de IA inválida');
                return redirect()->back();
            }

            // Buscar cliente por RUC (en pedidos usamos clientes, no proveedores)
            $cliente = null;
            if (!empty($aiData['cabecera']['ruc'])) {
                $cliente = DB::selectOne(
                    'SELECT id_cliente, CONCAT(clie_nombre, \' \', clie_apellido) as cliente,
                            clie_ci as ruc, clie_telefono as telefono, clie_direccion as direccion
                     FROM clientes
                     WHERE clie_ci = ? LIMIT 1',
                    [$aiData['cabecera']['ruc']]
                );
            }

            // Mapear productos por código de barras
            $productosEncontrados = [];
            $productosNoEncontrados = [];

            foreach ($aiData['detalle'] as $item) {
                $codigoProducto = $item['codigo_producto'] ?? null;

                if ($codigoProducto) {
                    $producto = DB::selectOne(
                        'SELECT p.*, t.porcentaje as tax_porcentaje
                         FROM productos p
                         LEFT JOIN taxes t ON p.id_tax = t.id_tax
                         WHERE p.codigo_barras = ? LIMIT 1',
                        [$codigoProducto]
                    );

                    if ($producto) {
                        $productosEncontrados[] = [
                            'producto' => $producto,
                            'cantidad' => $item['cantidad'] ?? 1,
                            'precio_ai' => $item['precio_unitario'] ?? 0,
                        ];
                    } else {
                        $productosNoEncontrados[] = $item;
                    }
                } else {
                    $productosNoEncontrados[] = $item;
                }
            }

            // Guardar PDF y datos procesados en sesión
            $pdfContent = base64_encode(file_get_contents($file->getRealPath()));
            $pdfName = time() . '_' . $file->getClientOriginalName();

            session([
                'ai_pdf_content' => $pdfContent,
                'ai_pdf_name' => $pdfName,
                'ai_processed_data' => [
                    'cabecera' => $aiData['cabecera'],
                    'cliente' => $cliente ? [
                        'id_cliente' => $cliente->id_cliente,
                        'cliente' => $cliente->cliente,
                        'ruc' => $cliente->ruc,
                        'direccion' => $cliente->direccion,
                        'telefono' => $cliente->telefono,
                    ] : null,
                    'productos_encontrados' => $productosEncontrados,
                    'productos_no_encontrados' => $productosNoEncontrados,
                    'controles' => $aiData['controles'],
                ]
            ]);

            // Redirigir a la vista de revisión
            return redirect()->route('pedidos.reviewAi');

        } catch (\Exception $e) {
            Log::error('AI PDF Processing Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Alert::error('Error', 'Error al procesar el PDF: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Mostrar vista de revisión de datos procesados por IA
     */
    public function reviewAi()
    {
        // Verificar si hay datos procesados en la sesión
        if (!session()->has('ai_processed_data')) {
            Alert::warning('Advertencia', 'No hay datos procesados. Por favor, sube un PDF primero.');
            return redirect()->route('pedidos.createWithAi');
        }

        $data = session('ai_processed_data');

        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre,' ', clie_apellido) AS cliente, clie_ci")
            ->selectRaw("CONCAT(clie_nombre,' ', clie_apellido, COALESCE(' - ' || clie_ci, '')) AS cliente_completo")
            ->pluck('cliente_completo', 'id_cliente');

        $sucursales = $this->getSucursalesPermitidas();
        $usuario = auth()->user()->name;

        return view('pedidos.review_ai', compact('data', 'clientes', 'sucursales', 'usuario'));
    }

    /**
     * Guardar pedido creado con IA
     */
    public function storeAi(Request $request)
    {
        $input = $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'fecha_pedido' => 'required|date',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'codigo' => 'required|array|min:1',
            'codigo.*' => 'required|integer',
            'cantidad' => 'required|array|min:1',
            'cantidad.*' => 'required|numeric|min:1',
            'precio' => 'required|array|min:1',
            'precio.*' => 'required',
        ], [
            'codigo.required' => 'Debe agregar al menos un producto.',
        ]);

        try {
            DB::beginTransaction();

            $user_id = auth()->user()->id;
            $total = 0.0;

            foreach ($input['codigo'] as $i => $codigo) {
                $precio = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);
                $subtotal = $cantidad * (float)$precio;
                $total += $subtotal;

                if ($cantidad <= 0 || (float)$precio <= 0) {
                    DB::rollback();
                    Alert::error('Error', 'Cantidad o precio inválidos');
                    return back()->withInput();
                }
            }

            // Guardar PDF si existe en sesión
            $pdfPath = null;
            if (session()->has('ai_pdf_content') && session()->has('ai_pdf_name')) {
                $pdfContent = base64_decode(session('ai_pdf_content'));
                $pdfName = session('ai_pdf_name');
                $pdfPath = 'pedidos/pdfs/' . $pdfName;
                Storage::disk('public')->put($pdfPath, $pdfContent);

                session()->forget(['ai_pdf_content', 'ai_pdf_name']);
            }

            // Insertar cabecera de pedido (sin totales de IVA aún)
            $idPedido = DB::table('pedidos')->insertGetId([
                'id_cliente'   => $input['id_cliente'],
                'fecha_pedido' => $input['fecha_pedido'],
                'total_pedido' => $total,
                'id_sucursal'  => $input['id_sucursal'] ?? null,
                'estado'       => 'PENDIENTE',
                'id_usuario'   => $user_id,
                'pdf_path'     => $pdfPath,
            ], 'id_pedido');

            // Obtener información de impuestos de los productos
            $codigos = array_map('intval', $input['codigo']);
            $productosInfo = DB::table('productos as p')
                ->leftJoin('taxes as t', 'p.id_tax', '=', 't.id_tax')
                ->whereIn('p.id_producto', $codigos)
                ->select('p.id_producto', 'p.id_tax', 't.porcentaje')
                ->get()
                ->keyBy('id_producto')
                ->toArray();

            // Inicializar totales de impuestos
            $total_exento = 0;
            $total_gravado_5 = 0;
            $total_gravado_10 = 0;
            $total_iva_5 = 0;
            $total_iva_10 = 0;

            // Insertar detalle del pedido con cálculo de impuestos
            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);

                // Obtener info del producto
                $productoInfo = $productosInfo[(int)$codigo] ?? null;
                $porcentaje_impuesto = $productoInfo->porcentaje ?? 0;
                $id_tax = $productoInfo->id_tax ?? null;

                // Calcular precio sin impuesto (Fórmula Paraguaya)
                $precio_sin_impuesto = $porcentaje_impuesto > 0
                    ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                    : $precio_con_iva;

                $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                // Calcular subtotales por item
                $gravada_item = $precio_sin_impuesto * $cantidad;
                $impuesto_total_item = $monto_impuesto * $cantidad;

                // Acumular totales según tipo de impuesto
                if ($porcentaje_impuesto == 0) {
                    $total_exento += $gravada_item;
                } elseif ($porcentaje_impuesto == 5) {
                    $total_gravado_5 += $gravada_item;
                    $total_iva_5 += $impuesto_total_item;
                } elseif ($porcentaje_impuesto == 10) {
                    $total_gravado_10 += $gravada_item;
                    $total_iva_10 += $impuesto_total_item;
                }

                // Insertar detalle con campos de impuesto
                DB::table('detalle_pedido')->insert([
                    'id_pedido'           => $idPedido,
                    'id_producto'         => (int)$codigo,
                    'cantidad'            => $cantidad,
                    'precio_unitario'     => (float)$precio_con_iva,
                    'id_tax'              => $id_tax,
                    'precio_sin_impuesto' => (float)$gravada_item,
                    'monto_impuesto'      => (float)$impuesto_total_item,
                ]);
            }

            // Actualizar totales de impuestos en el pedido
            $total_iva = $total_iva_5 + $total_iva_10;
            DB::update(
                'UPDATE pedidos SET total_exento = ?, total_gravado_5 = ?, total_gravado_10 = ?,
                 total_iva_5 = ?, total_iva_10 = ?, total_iva = ? WHERE id_pedido = ?',
                [
                    $total_exento,
                    $total_gravado_5,
                    $total_gravado_10,
                    $total_iva_5,
                    $total_iva_10,
                    $total_iva,
                    $idPedido
                ]
            );

            DB::commit();

            Alert::success('Éxito', 'Pedido creado correctamente con IA');
            return redirect()->route('pedidos.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Error al crear el Pedido: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Guardar nuevo cliente vía AJAX
     */
    public function storeClienteAjax(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'clie_nombre' => 'required',
                'clie_apellido' => 'required',
                'clie_ci' => 'nullable|string|max:20',
                'clie_telefono' => 'required',
                'clie_direccion' => 'nullable|string',
            ], [
                'clie_nombre.required' => 'Campo nombre obligatorio',
                'clie_apellido.required' => 'Campo apellido obligatorio',
                'clie_telefono.required' => 'Campo teléfono obligatorio'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar duplicado por CI/RUC
            if (!empty($input['clie_ci'])) {
                $existe = DB::selectOne(
                    'SELECT * FROM clientes WHERE clie_ci = ?',
                    [$input['clie_ci']]
                );
                if ($existe) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['clie_ci' => ['Ya existe un cliente con este CI/RUC.']]
                    ], 422);
                }
            }

            // Obtener ciudad y departamento por defecto si no se proporcionan
            $id_ciudad = $input['id_ciudad'] ?? null;
            $id_departamento = $input['id_departamento'] ?? null;

            if (!$id_ciudad) {
                // Obtener la primera ciudad disponible como valor por defecto
                $ciudadDefault = DB::selectOne('SELECT id_ciudad, id_departamento FROM ciudades ORDER BY id_ciudad LIMIT 1');
                if ($ciudadDefault) {
                    $id_ciudad = $ciudadDefault->id_ciudad;
                    if (!$id_departamento) {
                        $id_departamento = $ciudadDefault->id_departamento;
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay ciudades disponibles en el sistema. Por favor, contacte al administrador.'
                    ], 500);
                }
            }

            // Si clie_ci está vacío, usar un valor temporal para evitar el NOT NULL constraint
            $clie_ci = !empty($input['clie_ci']) ? $input['clie_ci'] : 'SIN-CI-' . time();

            $nuevoCliente = DB::selectOne(
                'INSERT INTO clientes (clie_nombre, clie_apellido, clie_ci, clie_direccion, clie_telefono, id_ciudad, id_departamento)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 RETURNING id_cliente, clie_nombre, clie_apellido, clie_ci',
                [
                    $input['clie_nombre'],
                    $input['clie_apellido'],
                    $clie_ci,
                    $input['clie_direccion'] ?? null,
                    $input['clie_telefono'],
                    $id_ciudad,
                    $id_departamento
                ]
            );

            if (!$nuevoCliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el cliente'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'cliente' => [
                    'id_cliente' => $nuevoCliente->id_cliente,
                    'cliente' => $nuevoCliente->clie_nombre . ' ' . $nuevoCliente->clie_apellido,
                    'ruc' => $nuevoCliente->clie_ci
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en storeClienteAjax: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}
