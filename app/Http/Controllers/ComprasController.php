<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\ComprasExport;
use Maatwebsite\Excel\Facades\Excel;

class ComprasController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:compras index')->only(['index', 'show']);
        $this->middleware('permission:compras create')->only(['create', 'store', 'buscarProducto', 'crearDesdeOrden']);
        $this->middleware('permission:compras edit')->only(['edit', 'update']);
        $this->middleware('permission:compras destroy')->only(['destroy']);
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
            $sql_filtros .= " AND c.fecha_compra BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            // Solo fecha desde (mayor o igual)
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND c.fecha_compra >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            // Solo fecha hasta (menor o igual)
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND c.fecha_compra <= '{$fecha_hasta}'";
        }

        // FILTRO POR PROVEEDOR
        if (!empty($input['id_proveedor'])) {
            $sql_filtros .= " AND c.id_proveedor = " . (int)$input['id_proveedor'];
        }

        // FILTRO POR CONDICIÓN DE COMPRA
        if (!empty($input['condicion_compra'])) {
            $sql_filtros .= " AND c.condicion_compra = '" . $input['condicion_compra'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND c.id_sucursal = " . (int)$input['id_sucursal'];
        }

        // Obtener el término de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $sql_busqueda = 'WHERE 1=1';

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql_busqueda = "WHERE c.id_compra = " . (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd
                $buscarFecha = $buscarTerm;
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }

                // Limpiar separadores de miles para búsqueda de montos (3.200.000 -> 3200000)
                $buscarMonto = str_replace('.', '', $buscarTerm);

                $sql_busqueda = "WHERE (cast(c.id_compra as text) ILIKE '%{$buscarTerm}%'
                       OR cast(c.fecha_compra as text) ILIKE '%{$buscarFecha}%'
                       OR TO_CHAR(c.fecha_compra, 'DD/MM/YYYY') ILIKE '%{$buscarTerm}%'
                       OR c.condicion_compra ILIKE '%{$buscarTerm}%'
                       OR c.factura ILIKE '%{$buscarTerm}%'
                       OR p.descripcion ILIKE '%{$buscarTerm}%'
                       OR cast(c.id_proveedor as text) ILIKE '%{$buscarTerm}%'
                       OR u.name ILIKE '%{$buscarTerm}%'
                       OR c.estado ILIKE '%{$buscarTerm}%'
                       OR cast(c.total as text) ILIKE '%{$buscarMonto}%'
                       OR TO_CHAR(c.total, 'FM999G999G999G999G999') ILIKE '%{$buscarTerm}%')";
            }
        }

        $idsPermitidos = $this->getSucursalesPermitidasIds();
        $sqlSucursalesPermitidas = '';
        if (!empty($idsPermitidos)) {
            $sqlSucursalesPermitidas = ' AND c.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        }

        // Exportar a Excel si se solicita
        if (isset($input['exportar']) && $input['exportar'] == 'excel') {
            $filtros = [
                'fecha_desde' => $input['fecha_desde'] ?? null,
                'fecha_hasta' => $input['fecha_hasta'] ?? null,
                'id_proveedor' => $input['id_proveedor'] ?? null,
                'condicion_compra' => $input['condicion_compra'] ?? null,
                'id_sucursal' => $input['id_sucursal'] ?? null,
            ];
            return Excel::download(new ComprasExport($filtros, $buscar, $idsPermitidos), 'compras.xlsx');
        }

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        // 'field' says which property of the row holds the value and 'format'
        // how to print it; both are only read by the export.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'c.id_compra', 'field' => 'id_compra', 'first' => 'desc'],
            'fecha' => ['label' => 'Fecha', 'sort' => 'c.fecha_compra', 'field' => 'fecha_compra', 'format' => 'date', 'tiebreaker' => 'c.id_compra', 'first' => 'desc'],
            'condicion' => ['label' => 'Condicion', 'sort' => 'c.condicion_compra', 'field' => 'condicion_compra'],
            'factura' => ['label' => 'Factura', 'sort' => 'c.factura'],
            'proveedor' => ['label' => 'Proveedor', 'sort' => 'p.descripcion'],
            'usuario' => ['label' => 'Usuario', 'sort' => 'u.name'],
            'estado' => ['label' => 'Estado', 'sort' => 'c.estado'],
            'total' => ['label' => 'Total', 'sort' => 'c.total', 'format' => 'money', 'align' => 'right', 'first' => 'desc'],
            'acciones' => ['label' => 'Acciones', 'width' => '140px', 'export' => false],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'fecha', 'desc', $request);

        $seleccion = "SELECT c.*,
                    p.descripcion AS proveedor,
                    u.name        AS usuario,
                    s.descripcion AS sucursal
               FROM compras c
          LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
          LEFT JOIN users u       ON c.user_id = u.id
          LEFT JOIN sucursales s  ON c.id_sucursal = s.id_sucursal
               {$sql_busqueda} {$sql_filtros} {$sqlSucursalesPermitidas}
               ORDER BY {$sort->orderBy()}";

        // Exports run the same query without the page window, so the file
        // carries every row the current filters match, not just this page.
        if ($export = \App\Support\TableExport::fromRequest($request, $columns)) {
            return $export->download(fn () => DB::select($seleccion . ' LIMIT ' . \App\Support\TableExport::MAX_ROWS), 'compras');
        }

        $compras = DB::select($seleccion);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($compras);            // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($compras, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $compras = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('compras.table')
                ->with('compras', $compras)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        // Obtener datos para los filtros
        $proveedores = DB::table('proveedores')->selectRaw("id_proveedor, descripcion")->pluck('descripcion', 'id_proveedor');
        $sucursales = $this->getSucursalesPermitidas();
        $condiciones_compra = ['CONTADO' => 'CONTADO', 'CREDITO' => 'CREDITO'];

        return view('compras.index', compact('compras', 'proveedores', 'sucursales', 'condiciones_compra'))
            ->with('columns', $columns)
            ->with('sort', $sort);
    }
    
    public function create()
    {
        $proveedores = $this->kvProveedores(); // [id_proveedor => descripcion]
        $usuario     = auth()->user()->name ?? '';
        $user_id     = auth()->id();
        $sucursales  = $this->getSucursalesPermitidas();

        // Campos de crédito
        $condicion_compra = ['CONTADO' => 'CONTADO', 'CREDITO' => 'CREDITO'];
        $intervalo = [
            '7'  => '7 Días',
            '15' => '15 Días',
            '30' => '30 Días'
        ];
        // Cantidad de intervalos (cuotas) 1..12
        $cantidad_cuotas = [];
        for ($i = 1; $i <= 12; $i++) {
            $cantidad_cuotas[(string)$i] = (string)$i;
        }

        return view('compras.create', compact('proveedores', 'usuario', 'user_id', 'sucursales', 'condicion_compra', 'intervalo', 'cantidad_cuotas'));
    }
    
    public function buscarProducto(Request $request)
    {
        $buscar = trim((string)$request->get('query', ''));

        if ($buscar !== '') {
            $buscarLower = mb_strtolower($buscar, 'UTF-8');
            $sql = "SELECT p.*, t.porcentaje as tax_porcentaje
                    FROM productos p
                    LEFT JOIN taxes t ON p.id_tax = t.id_tax
                    WHERE p.activo = true
                    AND (LOWER(p.descripcion) LIKE ? OR CAST(p.id_producto AS TEXT) LIKE ?)
                    LIMIT 50";
            $productos = DB::select($sql, ["%{$buscarLower}%", "%{$buscarLower}%"]);
        } else {
            $sql = "SELECT p.*, t.porcentaje as tax_porcentaje
                    FROM productos p
                    LEFT JOIN taxes t ON p.id_tax = t.id_tax
                    WHERE p.activo = true
                    LIMIT 20";
            $productos = DB::select($sql);
        }

        return view('compras.body_producto')->with('productos', $productos);
    }
    
    public function store(Request $request)
    {
        $input = $request->all();
        // Limpiar campos cuando es CONTADO
        if (isset($input['condicion_compra']) && $input['condicion_compra'] === 'CONTADO') {
            $input['intervalo'] = null;
            $input['cantidad_cuotas'] = null;
        }

        $validator = Validator::make($input, [
            'id_proveedor'        => 'required|exists:proveedores,id_proveedor',
            'fecha_compra'        => 'required|date',
            'user_id'             => 'required|exists:users,id',
            'id_sucursal'         => 'required|exists:sucursales,id_sucursal',

            'factura'             => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('compras', 'factura')->where(function ($query) use ($input) {
                    return $query->where('id_proveedor', $input['id_proveedor'] ?? null);
                }),
            ],

            'condicion_compra'    => 'required|in:CONTADO,CREDITO',
            'intervalo'           => 'nullable|required_if:condicion_compra,CREDITO|in:7,15,30',
            'cantidad_cuotas'     => 'nullable|required_if:condicion_compra,CREDITO|integer|min:1|max:36',

            // detalle desde vista (igual que ventas): codigo[], cantidad[], precio[]
            'codigo'              => 'required|array|min:1',
            'codigo.*'            => 'required|integer',
            'cantidad'            => 'required|array|min:1',
            'cantidad.*'          => 'required|numeric|min:1',
            'precio_unitario'              => 'required|array|min:1',
            'precio_unitario.*'            => 'required',
        ], [
            'id_proveedor.required'        => 'El proveedor es obligatorio.',
            'id_proveedor.exists'          => 'El proveedor no es válido.',
            'fecha_compra.required'        => 'La fecha es obligatoria.',
            'fecha_compra.date'            => 'La fecha no es válida.',
            'user_id.required'             => 'El usuario es obligatorio.',
            'user_id.exists'               => 'El usuario no es válido.',
            'id_sucursal.required'         => 'La sucursal es obligatoria.',
            'id_sucursal.exists'           => 'La sucursal no es válida.',
            'condicion_compra.required'    => 'La condición es obligatoria.',
            'condicion_compra.in'          => 'La condición no es válida.',
            'intervalo.required_if'        => 'El intervalo es obligatorio cuando es crédito.',
            'intervalo.in'                 => 'El intervalo debe ser 7, 15 o 30 días.',
            'cantidad_cuotas.required_if'  => 'La cantidad de cuotas es obligatoria cuando es crédito.',
            'cantidad_cuotas.integer'      => 'La cantidad de cuotas debe ser un número entero.',
            'cantidad_cuotas.min'          => 'La cantidad de cuotas debe ser al menos 1 cuando es crédito.', // CORREGIDO
            'cantidad_cuotas.max'          => 'La cantidad de cuotas no debe superar 36.',
            'codigo.required'              => 'Debe agregar al menos un producto.',
            'factura.unique'               => 'Ya existe una compra con esta factura para el proveedor seleccionado.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $idSucursal = (int)$input['id_sucursal'];

        // Inicializar totales
        $total = 0.0;
        $total_exento = 0;
        $total_gravado_5 = 0;
        $total_gravado_10 = 0;
        $total_iva_5 = 0;
        $total_iva_10 = 0;

        // Obtener información de productos incluyendo impuestos
        $codigos = array_map('intval', $input['codigo'] ?? []);
        $productosInfo = !empty($codigos)
            ? DB::table('productos as p')
                ->leftJoin('taxes as t', 'p.id_tax', '=', 't.id_tax')
                ->whereIn('p.id_producto', $codigos)
                ->select('p.id_producto', 'p.id_tax', 't.porcentaje')
                ->get()
                ->keyBy('id_producto')
                ->toArray()
            : [];

        DB::beginTransaction();
        try {
            // Si es contado, aseguramos que sean null (ya se limpiaron arriba)
            if (($input['condicion_compra'] ?? 'CONTADO') === 'CONTADO') {
                $input['intervalo'] = null;
                $input['cantidad_cuotas'] = null;
            }

            // Cabecera (insertamos con totales iniciales, luego actualizamos)
            $idCompra = DB::table('compras')->insertGetId([
                'id_proveedor'        => (int)$input['id_proveedor'],
                'fecha_compra'        => $input['fecha_compra'],
                'total'               => 0, // Se actualizará después
                'user_id'             => (int)$input['user_id'],
                'id_sucursal'         => $idSucursal,
                'factura'             => $input['factura'] ?? null,
                'condicion_compra'    => $input['condicion_compra'],
                'intervalo'           => $input['intervalo'],
                'cantidad_cuotas'     => $input['cantidad_cuotas'],
            ], 'id_compra');

            // Detalle + AUMENTO de stock por sucursal + Cálculo de IVA
            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = str_replace('.', '', $input['precio_unitario'][$i] ?? '0');
                $cant  = (int)($input['cantidad'][$i] ?? 0);

                // Obtener información del producto
                $productoInfo = $productosInfo[(int)$codigo] ?? null;

                if (!$productoInfo || !$productoInfo->id_tax) {
                    // Producto sin impuesto - ERROR
                    DB::rollback();
                    Alert::error('Error', 'El producto con ID ' . $codigo . ' no tiene un impuesto asignado. Todos los productos deben tener impuesto.');
                    return back()->withInput();
                }

                $porcentaje_impuesto = $productoInfo->porcentaje;
                $id_tax = $productoInfo->id_tax;

                // Calcular precio sin impuesto usando la fórmula paraguaya
                $precio_sin_impuesto = $porcentaje_impuesto > 0
                    ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                    : $precio_con_iva;

                // Calcular monto del impuesto
                $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                // Calcular subtotales por item
                $subtotal_item = $precio_con_iva * $cant;
                $gravada_item = $precio_sin_impuesto * $cant;
                $impuesto_total_item = $monto_impuesto * $cant;
                $total += $subtotal_item;

                // Acumular en los totales según tipo de impuesto
                if ($porcentaje_impuesto == 0) {
                    $total_exento += $gravada_item;
                } elseif ($porcentaje_impuesto == 5) {
                    $total_gravado_5 += $gravada_item;
                    $total_iva_5 += $impuesto_total_item;
                } elseif ($porcentaje_impuesto == 10) {
                    $total_gravado_10 += $gravada_item;
                    $total_iva_10 += $impuesto_total_item;
                }

                // Insertar detalle con información de impuestos
                DB::table('detalle_compras')->insert([
                    'id_compra'           => $idCompra,
                    'id_producto'         => (int)$codigo,
                    'cantidad'            => $cant,
                    'precio_unitario'     => $precio_con_iva,
                    'id_tax'              => $id_tax,
                    'precio_sin_impuesto' => $precio_sin_impuesto,
                    'monto_impuesto'      => $monto_impuesto,
                ]);

                // stock += cantidad
                $this->increaseStock((int)$codigo, $cant, $idSucursal);
            }

            // Calcular total de IVA
            $total_iva = $total_iva_5 + $total_iva_10;
            $total = round($total, 2);

            // Actualizar totales en la cabecera
            DB::table('compras')->where('id_compra', $idCompra)->update([
                'total'           => $total,
                'total_exento'    => $total_exento,
                'total_gravado_5' => $total_gravado_5,
                'total_gravado_10'=> $total_gravado_10,
                'total_iva_5'     => $total_iva_5,
                'total_iva_10'    => $total_iva_10,
                'total_iva'       => $total_iva,
            ]);

            // Si la compra se creó desde una orden, actualizar el estado y vincular
            if (!empty($input['id_orden_compra'])) {
                DB::update('UPDATE ordenes_compra SET estado = ?, id_compra = ? WHERE id_orden_compra = ?', [
                    'PROCESADO',
                    $idCompra,
                    $input['id_orden_compra']
                ]);
            }

            DB::commit();
            Alert::success('Éxito', 'Compra registrada, stock actualizado.');
            return redirect()->route('compras.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en compras.store: ' . $e->getMessage());
            Alert::error('Error Al Agregar Compras: '. $e->getMessage());
            return back()->withErrors(['db' => $e->getMessage()])->withInput();
        }
    }
    
    public function show($id)
    {
        $compra = DB::selectOne(
            "SELECT c.*,
                    p.descripcion AS proveedor,
                    u.name        AS usuario,
                    s.descripcion AS sucursal
               FROM compras c
               JOIN proveedores p ON c.id_proveedor = p.id_proveedor
               JOIN users u       ON c.user_id = u.id
               JOIN sucursales s  ON c.id_sucursal = s.id_sucursal
              WHERE c.id_compra = ?",
            [$id]
        );
        if (!$compra) {
            Alert::error('Error', 'Compra no encontrada.');
            return redirect()->route('compras.index');
        }

        $detalles = DB::select(
            "SELECT d.*, pr.descripcion, t.porcentaje as tax_porcentaje
               FROM detalle_compras d
          LEFT JOIN productos pr ON pr.id_producto = d.id_producto
          LEFT JOIN taxes t ON pr.id_tax = t.id_tax
              WHERE d.id_compra = ?",
            [$id]
        );

        return view('compras.show', compact('compra'))->with('detalles', $detalles);
    }
    
    public function edit($id)
    {
        $compra = DB::selectOne("SELECT c.*, descripcion AS sucursal FROM compras c JOIN sucursales USING(id_sucursal)   WHERE id_compra = ?", [$id]);
        if (!$compra) {
            Alert::error('Error', 'Compra no encontrada.');
            return redirect()->route('compras.index');
        }

        $proveedores = $this->kvProveedores(); // [id_proveedor => descripcion]
        $usuario     = auth()->user()->name ?? '';
        $user_id     = auth()->id();
        $condicion_compra = [
            'CONTADO' => 'CONTADO',
            'CREDITO' => 'CREDITO'
        ];

        $intervalo = [
            '7'  => '7 Días',
            '15' => '15 Días',
            '30' => '30 Días'
        ];

        // Enviar datos de sucursales
        $sucursales = $this->getSucursalesPermitidas();

        $detalles = DB::select(
            "SELECT d.*, pr.descripcion, t.porcentaje as tax_porcentaje
               FROM detalle_compras d
          LEFT JOIN productos pr ON pr.id_producto = d.id_producto
          LEFT JOIN taxes t ON pr.id_tax = t.id_tax
              WHERE d.id_compra = ?",
            [$id]
        );

        return view('compras.edit', compact('compra', 'proveedores', 'usuario', 'user_id', 'intervalo', 'condicion_compra', 'sucursales'))
            ->with('detalles', $detalles);
    }
    
    public function update(Request $request, $id)
    {
        $input = $request->all();
        // Limpiar campos cuando es CONTADO
        if (isset($input['condicion_compra']) && $input['condicion_compra'] === 'CONTADO') {
            $input['intervalo'] = null;
            $input['cantidad_cuotas'] = null;
        }

        $validator = Validator::make($input, [
            'id_proveedor'        => 'required|exists:proveedores,id_proveedor',
            'fecha_compra'        => 'required|date',
            'user_id'             => 'required|exists:users,id',
            'id_sucursal'         => 'required|exists:sucursales,id_sucursal',

            'factura'             => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('compras', 'factura')
                    ->where(function ($query) use ($input) {
                        return $query->where('id_proveedor', $input['id_proveedor'] ?? null);
                    })
                    ->ignore($id, 'id_compra'),
            ],

            'condicion_compra'    => 'required|in:CONTADO,CREDITO',
            'intervalo'           => 'nullable|required_if:condicion_compra,CREDITO|in:7,15,30',
            'cantidad_cuotas'     => 'nullable|required_if:condicion_compra,CREDITO|integer|min:1|max:36',

            'codigo'              => 'required|array|min:1',
            'codigo.*'            => 'required|integer',
            'cantidad'            => 'required|array|min:1',
            'cantidad.*'          => 'required|numeric|min:1',
            'precio_unitario'     => 'required|array|min:1',
            'precio_unitario.*'   => 'required',
        ], [
            'id_proveedor.required'        => 'El proveedor es obligatorio.',
            'id_proveedor.exists'          => 'El proveedor no es válido.',
            'fecha_compra.required'        => 'La fecha es obligatoria.',
            'fecha_compra.date'            => 'La fecha no es válida.',
            'user_id.required'             => 'El usuario es obligatorio.',
            'user_id.exists'               => 'El usuario no es válido.',
            'id_sucursal.required'         => 'La sucursal es obligatoria.',
            'id_sucursal.exists'           => 'La sucursal no es válida.',
            'condicion_compra.required'    => 'La condición es obligatoria.',
            'condicion_compra.in'          => 'La condición no es válida.',
            'intervalo.required_if'        => 'El intervalo es obligatorio cuando es crédito.',
            'intervalo.in'                 => 'El intervalo debe ser 7, 15 o 30 días.',
            'cantidad_cuotas.required_if'  => 'La cantidad de cuotas es obligatoria cuando es crédito.',
            'cantidad_cuotas.integer'      => 'La cantidad de cuotas debe ser un número entero.',
            'cantidad_cuotas.min'          => 'La cantidad de cuotas debe ser al menos 1 cuando es crédito.', // CORREGIDO
            'cantidad_cuotas.max'          => 'La cantidad de cuotas no debe superar 36.',
            'codigo.required'              => 'Debe agregar al menos un producto.',
            'factura.unique'               => 'Ya existe una compra con esta factura para el proveedor seleccionado.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $idSucursal = (int)$request->input('id_sucursal');

        // Mapa actual (antes de cambios) para ajustar stock por diferencia
        $detalleAnt = DB::table('detalle_compras')->where('id_compra', $id)->get();
        $mapAnt = [];
        foreach ($detalleAnt as $d) {
            Log::error('Detalle actual: id_producto=' . $d->id_producto . ', cantidad=' . $d->cantidad);
            $mapAnt[$d->id_producto] = ($mapAnt[$d->id_producto] ?? 0) + (int)$d->cantidad;
        }

        // Inicializar totales
        $total = 0.0;
        $total_exento = 0;
        $total_gravado_5 = 0;
        $total_gravado_10 = 0;
        $total_iva_5 = 0;
        $total_iva_10 = 0;

        // Obtener información de productos incluyendo impuestos
        $codigos = array_map('intval', $input['codigo'] ?? []);
        $productosInfo = !empty($codigos)
            ? DB::table('productos as p')
                ->leftJoin('taxes as t', 'p.id_tax', '=', 't.id_tax')
                ->whereIn('p.id_producto', $codigos)
                ->select('p.id_producto', 'p.id_tax', 't.porcentaje')
                ->get()
                ->keyBy('id_producto')
                ->toArray()
            : [];

        // Mapa nuevo + calcular totales con IVA
        $mapNuevo = [];
        foreach ($input['codigo'] as $i => $codigo) {
            $precio_con_iva = $this->normalizeNumber(str_replace('.', '', $input['precio_unitario'][$i] ?? '0'));
            $cant  = (int)($input['cantidad'][$i] ?? 0);
            $mapNuevo[(int)$codigo] = ($mapNuevo[(int)$codigo] ?? 0) + $cant;

            // Obtener información del producto para calcular IVA
            $productoInfo = $productosInfo[(int)$codigo] ?? null;

            if ($productoInfo && $productoInfo->id_tax) {
                $porcentaje_impuesto = $productoInfo->porcentaje;

                // Calcular precio sin impuesto
                $precio_sin_impuesto = $porcentaje_impuesto > 0
                    ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                    : $precio_con_iva;

                // Calcular monto del impuesto
                $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                // Calcular subtotales por item
                $subtotal_item = $precio_con_iva * $cant;
                $gravada_item = $precio_sin_impuesto * $cant;
                $impuesto_total_item = $monto_impuesto * $cant;
                $total += $subtotal_item;

                // Acumular en los totales según tipo de impuesto
                if ($porcentaje_impuesto == 0) {
                    $total_exento += $gravada_item;
                } elseif ($porcentaje_impuesto == 5) {
                    $total_gravado_5 += $gravada_item;
                    $total_iva_5 += $impuesto_total_item;
                } elseif ($porcentaje_impuesto == 10) {
                    $total_gravado_10 += $gravada_item;
                    $total_iva_10 += $impuesto_total_item;
                }
            } else {
                // Producto sin impuesto definido, sumar al total sin cálculos
                $total += $precio_con_iva * $cant;
            }
        }
        $total = round($total, 2);
        $total_iva = $total_iva_5 + $total_iva_10;

        DB::beginTransaction();
        try {
            // Si es contado, aseguramos que sean null (ya se limpiaron arriba)
            if (($input['condicion_compra'] ?? 'CONTADO') === 'CONTADO') {
                $input['intervalo'] = null;
                $input['cantidad_cuotas'] = null;
            }

            // Ajustar stock por diferencia
            $todosIds = array_unique(array_merge(array_keys($mapAnt), array_keys($mapNuevo)));
            foreach ($todosIds as $idProd) {
                $old   = (int)($mapAnt[$idProd] ?? 0);
                $new   = (int)($mapNuevo[$idProd] ?? 0);
                $delta = $new - $old;
                if ($delta > 0) {
                    $this->increaseStock($idProd, $delta, $idSucursal);
                } elseif ($delta < 0) {
                    $this->decreaseStock($idProd, abs($delta), $idSucursal);
                }
            }

            // Actualizar cabecera con totales de IVA
            DB::table('compras')->where('id_compra', $id)->update([
                'id_proveedor'        => (int)$input['id_proveedor'],
                'fecha_compra'        => $input['fecha_compra'],
                'total'               => $total,
                'user_id'             => (int)$input['user_id'],
                'id_sucursal'         => $idSucursal,
                'factura'             => $input['factura'] ?? null,
                'condicion_compra'    => $input['condicion_compra'],
                'intervalo'           => $input['intervalo'],
                'cantidad_cuotas'     => $input['cantidad_cuotas'],
                'total_exento'        => $total_exento,
                'total_gravado_5'     => $total_gravado_5,
                'total_gravado_10'    => $total_gravado_10,
                'total_iva_5'         => $total_iva_5,
                'total_iva_10'        => $total_iva_10,
                'total_iva'           => $total_iva,
            ]);

            // Reemplazar detalle con información de impuestos
            DB::table('detalle_compras')->where('id_compra', $id)->delete();
            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = $this->normalizeNumber(str_replace('.', '', $input['precio_unitario'][$i] ?? '0'));
                $cant  = (int)($input['cantidad'][$i] ?? 0);

                // Obtener información del producto
                $productoInfo = $productosInfo[(int)$codigo] ?? null;

                if ($productoInfo && $productoInfo->id_tax) {
                    $porcentaje_impuesto = $productoInfo->porcentaje;
                    $id_tax = $productoInfo->id_tax;

                    // Calcular precio sin impuesto
                    $precio_sin_impuesto = $porcentaje_impuesto > 0
                        ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                        : $precio_con_iva;

                    // Calcular monto del impuesto
                    $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                    DB::table('detalle_compras')->insert([
                        'id_compra'           => $id,
                        'id_producto'         => (int)$codigo,
                        'cantidad'            => $cant,
                        'precio_unitario'     => $precio_con_iva,
                        'id_tax'              => $id_tax,
                        'precio_sin_impuesto' => $precio_sin_impuesto,
                        'monto_impuesto'      => $monto_impuesto,
                    ]);
                } else {
                    // Producto sin impuesto
                    DB::table('detalle_compras')->insert([
                        'id_compra'       => $id,
                        'id_producto'     => (int)$codigo,
                        'cantidad'        => $cant,
                        'precio_unitario' => $precio_con_iva,
                    ]);
                }
            }

            DB::commit();
            Alert::success('Éxito', 'Compra actualizada y stock ajustado.');
            return redirect()->route('compras.show', $id);
        } catch (\Throwable $e) {
            DB::RollBack();
            Log::error('Error en compras.update: ' . $e->getMessage());
            Alert::error('Error', 'Error al actualizar compra: ' . $e->getMessage());
            return back()->withErrors(['db' => $e->getMessage()])->withInput();
        }
    }
    
    public function destroy($id)
    {
        // Si anulas compras, probablemente debas revertir el stock
        $compra = DB::selectOne("SELECT * FROM compras WHERE id_compra = ?", [$id]);
        if (!$compra) {
            Alert::error('Error', 'Compra no encontrada.');
            return redirect()->route('compras.index');
        }

        // Cargar detalle para ajustar stock en -
        $detalles = DB::table('detalle_compras')->where('id_compra', $id)->get();
        DB::beginTransaction();
        try {
            // Revertir stock
            foreach ($detalles as $d) {
                $this->decreaseStock((int)$d->id_producto, (int)$d->cantidad, (int)($compra->id_sucursal ?? 0));
            }

            // Marcar estado si existe; si no, opcionalmente borrar detalle + cabecera
            if (DB::getSchemaBuilder()->hasColumn('compras', 'estado')) {
                DB::update("UPDATE compras SET estado = 'ANULADO' WHERE id_compra = ?", [$id]);
            }

            DB::commit();
            Alert::success('Éxito', 'Compra anulada y stock revertido.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en compras.destroy: ' . $e->getMessage());
            Alert::error('Error', 'No se pudo anular la compra: ' . $e->getMessage());
            return back()->withErrors(['db' => $e->getMessage()]);
        }

        return redirect()->route('compras.index');
    }
    
    private function kvProveedores(): array
    {
        try {
            return DB::table('proveedores')->orderBy('descripcion')->pluck('descripcion', 'id_proveedor')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Normaliza "1.234.567,89" -> 1234567.89 y "1234.50" -> 1234.50 */
    private function normalizeNumber($value): float
    {
        if (is_null($value)) return 0.0;
        if (is_numeric($value)) return (float)$value;
        $s = trim((string)$value);
        if ($s === '') return 0.0;
        $s = str_replace([' ', "\u{00A0}"], '', $s); // espacios
        $s = str_replace('.', '', $s);               // miles
        $s = str_replace(',', '.', $s);              // decimal
        return (float)(is_numeric($s) ? $s : 0.0);
    }

    /** Aumenta stock (global o por sucursal) */
    private function shouldControlStock(int $idProducto): bool
    {
        static $cache = [];

        if (!array_key_exists($idProducto, $cache)) {
            $valor = DB::table('productos')->where('id_producto', $idProducto)->value('controlar_stock');
            $cache[$idProducto] = $valor === null ? true : (bool) $valor;
        }

        return $cache[$idProducto];
    }

    private function increaseStock(int $idProducto, int $cantidad, $idSucursal = null): void
    {
        if ($cantidad <= 0) return;
        if (!$this->shouldControlStock($idProducto)) return;
        if (!DB::getSchemaBuilder()->hasTable('stocks')) return;

        $hasSucursal = DB::getSchemaBuilder()->hasColumn('stocks', 'id_sucursal');

        if ($hasSucursal) {
            if ($idSucursal === null) {
                Log::warning('increaseStock: id_sucursal no provisto; no se actualiza stock por sucursal.');
                return;
            }
            $row = DB::table('stocks')->where([
                'id_producto' => $idProducto,
                'id_sucursal' => $idSucursal,
            ])->first();

            if ($row) {
                DB::table('stocks')->where([
                    'id_producto' => $idProducto,
                    'id_sucursal' => $idSucursal,
                ])->update(['cantidad' => ((int)$row->cantidad) + $cantidad]);
            } else {
                DB::table('stocks')->insert([
                    'id_producto' => $idProducto,
                    'id_sucursal' => $idSucursal,
                    'cantidad'    => $cantidad,
                ]);
            }
        } else {
            $row = DB::table('stocks')->where('id_producto', $idProducto)->first();
            if ($row) {
                DB::table('stocks')->where('id_producto', $idProducto)->update(['cantidad' => ((int)$row->cantidad) + $cantidad]);
            } else {
                DB::table('stocks')->insert(['id_producto' => $idProducto, 'cantidad' => $cantidad]);
            }
        }
    }

    /** Disminuye stock (global o por sucursal) */
    private function decreaseStock(int $idProducto, int $cantidad, $idSucursal = null): void
    {
        if ($cantidad <= 0) return;
        if (!$this->shouldControlStock($idProducto)) return;
        if (!DB::getSchemaBuilder()->hasTable('stocks')) return;

        $hasSucursal = DB::getSchemaBuilder()->hasColumn('stocks', 'id_sucursal');

        if ($hasSucursal) {
            if ($idSucursal === null) {
                Log::warning('decreaseStock: id_sucursal no provisto; no se actualiza stock por sucursal.');
                return;
            }
            $row = DB::table('stocks')->where([
                'id_producto' => $idProducto,
                'id_sucursal' => $idSucursal,
            ])->first();

            if ($row) {
                $newQty = max(0, ((int)$row->cantidad) - $cantidad);
                DB::table('stocks')->where([
                    'id_producto' => $idProducto,
                    'id_sucursal' => $idSucursal,
                ])->update(['cantidad' => $newQty]);
            }
        } else {
            $row = DB::table('stocks')->where('id_producto', $idProducto)->first();
            if ($row) {
                $newQty = max(0, ((int)$row->cantidad) - $cantidad);
                DB::table('stocks')->where('id_producto', $idProducto)->update(['cantidad' => $newQty]);
            }
        }
    }

    /**
     * Crear compra desde orden de compra
     */
    public function crearDesdeOrden($id)
    {
        try {
            // Obtener la orden de compra
            $orden = DB::selectOne('SELECT * FROM ordenes_compra WHERE id_orden_compra = ?', [$id]);

            if (empty($orden)) {
                Alert::error('Error', 'Orden de compra no encontrada');
                return redirect()->route('compras.index');
            }

            // Validar que la orden esté PENDIENTE o APROBADA
            if (!in_array($orden->estado, ['PENDIENTE', 'APROBADA'])) {
                Alert::warning('Advertencia', 'Solo se pueden procesar órdenes en estado PENDIENTE o APROBADA');
                return redirect()->route('compras.index');
            }

            // Obtener los detalles de la orden
            $detalles = DB::select(
                "SELECT d.id_producto, d.cantidad, d.precio_unitario, p.descripcion, t.porcentaje as tax_porcentaje
                 FROM detalle_orden_compra d
                 JOIN productos p ON d.id_producto = p.id_producto
                 LEFT JOIN taxes t ON p.id_tax = t.id_tax
                 WHERE d.id_orden_compra = ?",
                [$id]
            );

            // Datos para la vista
            $proveedores = $this->kvProveedores();
            $usuario = auth()->user()->name ?? '';
            $user_id = auth()->id();
            $sucursales = $this->getSucursalesPermitidas();

            $condicion_compra = ['CONTADO' => 'CONTADO', 'CREDITO' => 'CREDITO'];
            $intervalo = [
                '7'  => '7 Días',
                '15' => '15 Días',
                '30' => '30 Días'
            ];

            $cantidad_cuotas = [];
            for ($i = 1; $i <= 12; $i++) {
                $cantidad_cuotas[(string)$i] = (string)$i;
            }

            // Crear objeto compra simulado con datos de la orden
            $compra = new \stdClass();
            $compra->id_proveedor = $orden->id_proveedor;
            $compra->id_sucursal = $orden->id_sucursal;
            $compra->total = $orden->total;
            $compra->fecha_compra = \Carbon\Carbon::now()->format('Y-m-d');
            $compra->factura = null;
            $compra->condicion_compra = 'CONTADO';
            $compra->intervalo = null;
            $compra->cantidad_cuotas = null;

            return view('compras.create', compact(
                'orden',
                'detalles',
                'compra',
                'proveedores',
                'usuario',
                'user_id',
                'sucursales',
                'condicion_compra',
                'intervalo',
                'cantidad_cuotas'
            ))->with('desde_orden', true);

        } catch (\Throwable $e) {
            Log::error('Error al crear compra desde orden: ' . $e->getMessage());
            Alert::error('Error', 'Error al procesar la orden: ' . $e->getMessage());
            return redirect()->route('compras.index');
        }
    }
}
