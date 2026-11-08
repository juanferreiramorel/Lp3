<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use RealRashid\SweetAlert\Facades\Alert;
use App\Exports\OrdenCompraExport;
use Maatwebsite\Excel\Facades\Excel;
// use App\Services\AiOcrService; // Funcionalidad de IA migrada a Pedidos
// use Illuminate\Support\Facades\Storage; // Funcionalidad de IA migrada a Pedidos
// use Illuminate\Support\Facades\Validator; // Funcionalidad de IA migrada a Pedidos

class OrdenCompraController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ordencompra index')->only(['index', 'show', 'getPendientes']);
        $this->middleware('permission:ordencompra create')->only(['create', 'store', 'buscarProducto']); // Métodos de IA removidos
        $this->middleware('permission:ordencompra edit')->only(['edit', 'update']);
        $this->middleware('permission:ordencompra destroy')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND oc.fecha_orden BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND oc.fecha_orden >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND oc.fecha_orden <= '{$fecha_hasta}'";
        }

        // FILTRO POR PROVEEDOR
        if (!empty($input['id_proveedor'])) {
            $sql_filtros .= " AND oc.id_proveedor = " . (int)$input['id_proveedor'];
        }

        // FILTRO POR ESTADO
        if (!empty($input['estado'])) {
            $sql_filtros .= " AND oc.estado = '" . $input['estado'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND oc.id_sucursal = " . (int)$input['id_sucursal'];
        }

        $buscar = $request->buscar;
        $searchMode = $request->get('search_mode', 'all');
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // Query base
        $queryBase = "FROM ordenes_compra oc
                      JOIN proveedores p ON oc.id_proveedor = p.id_proveedor
                      LEFT JOIN users u ON oc.id_usuario = u.id
                      LEFT JOIN sucursales s ON oc.id_sucursal = s.id_sucursal";

        // Condiciones de búsqueda
        $where = "WHERE 1=1" . $sql_filtros;
        $params = [];

        $idsPermitidos = $this->getSucursalesPermitidasIds();
        if (!empty($idsPermitidos)) {
            $where .= ' AND oc.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        }

        // Exportar a Excel si se solicita
        if (isset($input['exportar']) && $input['exportar'] == 'excel') {
            $filtros = [
                'fecha_desde' => $input['fecha_desde'] ?? null,
                'fecha_hasta' => $input['fecha_hasta'] ?? null,
                'id_proveedor' => $input['id_proveedor'] ?? null,
                'estado' => $input['estado'] ?? null,
                'id_sucursal' => $input['id_sucursal'] ?? null,
            ];
            return Excel::download(new OrdenCompraExport($filtros, $buscar, $idsPermitidos), 'ordenes_compra.xlsx');
        }

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $where .= " AND oc.id_orden_compra = ?";
                $params[] = (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];
                $where .= " AND (
                    cast(oc.id_orden_compra as text) ILIKE ? OR
                    p.descripcion ILIKE ? OR
                    cast(oc.fecha_orden as text) ILIKE ? OR
                    TO_CHAR(oc.fecha_orden, 'DD/MM/YYYY') ILIKE ? OR
                    cast(oc.total as text) ILIKE ? OR
                    TO_CHAR(oc.total, 'FM999G999G999G999G999') ILIKE ? OR
                u.name ILIKE ? OR
                oc.estado ILIKE ? OR
                s.descripcion ILIKE ?
                )";
                $searchTerm = "%{$buscarTerm}%";
                $params = array_fill(0, 9, $searchTerm);
            }
        }

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        $columns = [
            'id' => ['label' => 'N° OC', 'sort' => 'oc.id_orden_compra', 'first' => 'desc'],
            'proveedor' => ['label' => 'Proveedor', 'sort' => 'p.descripcion'],
            'fecha' => ['label' => 'Fecha Orden', 'sort' => 'oc.fecha_orden', 'tiebreaker' => 'oc.id_orden_compra', 'first' => 'desc'],
            'total' => ['label' => 'Total', 'sort' => 'oc.total', 'first' => 'desc'],
            'estado' => ['label' => 'Estado', 'sort' => 'oc.estado'],
            'sucursal' => ['label' => 'Sucursal', 'sort' => 's.descripcion'],
            'usuario' => ['label' => 'Usuario', 'sort' => 'u.name'],
            'acciones' => ['label' => 'Acciones', 'width' => '140px'],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$where}";
        $totalResult = DB::select($totalQuery, $params);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT oc.id_orden_compra, oc.fecha_orden, oc.total, oc.estado,
                         oc.observaciones,
                         p.descripcion as proveedor,
                         u.name as usuario,
                         s.descripcion as sucursal
                  {$queryBase} {$where}
                  ORDER BY {$sort->orderBy()}
                  LIMIT {$porPagina} OFFSET {$offset}";

        $ordenesCompra = DB::select($query, $params);

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        // Si es petición AJAX, solo retornar la tabla
        if ($request->ajax()) {
            return view('ordencompra.table', compact('ordenesCompra', 'page', 'totalPages', 'total'))
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        // Obtener datos para los filtros
        $proveedores = DB::table('proveedores')
            ->pluck('descripcion', 'id_proveedor');
        $sucursales = $this->getSucursalesPermitidas();
        $estados = [
            'PENDIENTE' => 'Pendiente',
            'CANCELADA' => 'Cancelada',
            'PROCESADO' => 'Procesado'
        ];

        return view('ordencompra.index', compact('ordenesCompra', 'page', 'totalPages', 'total', 'proveedores', 'sucursales', 'estados'))
            ->with('columns', $columns)
            ->with('sort', $sort);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Cargar proveedores
        $proveedores = DB::table('proveedores')
            ->pluck('descripcion', 'id_proveedor');

        // Cargar sucursales
        $sucursales = $this->getSucursalesPermitidas();

        // Usuario autenticado
        $usuario = auth()->user()->name;

        return view('ordencompra.create', compact('proveedores', 'sucursales', 'usuario'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->validate([
            'id_proveedor' => 'required|exists:proveedores,id_proveedor',
            'fecha_orden' => 'required|date',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'observaciones' => 'nullable|string',
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

            // Inicializar totales
            $total = 0.0;
            $subtotal = 0.0;
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

            // Calcular totales desde el detalle
            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = (float)str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);

                // Obtener información del producto
                $productoInfo = $productosInfo[(int)$codigo] ?? null;

                if ($productoInfo && $productoInfo->id_tax) {
                    $porcentaje_impuesto = $productoInfo->porcentaje;

                    // Calcular precio sin impuesto usando la fórmula paraguaya
                    $precio_sin_impuesto = $porcentaje_impuesto > 0
                        ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                        : $precio_con_iva;

                    // Calcular monto del impuesto
                    $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                    // Calcular subtotales por item
                    $subtotal_item = $precio_con_iva * $cantidad;
                    $gravada_item = $precio_sin_impuesto * $cantidad;
                    $impuesto_total_item = $monto_impuesto * $cantidad;
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
                    $total += $precio_con_iva * $cantidad;
                    $total_exento += $precio_con_iva * $cantidad;
                }
            }

            // Calcular subtotal y total de IVA
            $subtotal = $total_exento + $total_gravado_5 + $total_gravado_10;
            $total_iva = $total_iva_5 + $total_iva_10;
            $total = round($total, 2);

            // Insertar cabecera con todos los totales de IVA
            $idOrdenCompra = DB::table('ordenes_compra')->insertGetId([
                'id_proveedor' => $input['id_proveedor'],
                'fecha_orden' => $input['fecha_orden'],
                'subtotal' => $subtotal,
                'total_exento' => $total_exento,
                'total_gravado_5' => $total_gravado_5,
                'total_gravado_10' => $total_gravado_10,
                'total_iva_5' => $total_iva_5,
                'total_iva_10' => $total_iva_10,
                'total_iva' => $total_iva,
                'total' => $total,
                'id_sucursal' => $input['id_sucursal'],
                'estado' => 'PENDIENTE',
                'id_usuario' => auth()->user()->id,
                'observaciones' => $input['observaciones'] ?? null,
            ], 'id_orden_compra');

            // Insertar detalle
            foreach ($input['codigo'] as $i => $codigo) {
                $precio = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);
                $subtotal = $cantidad * (float)$precio;

                DB::table('detalle_orden_compra')->insert([
                    'id_orden_compra' => $idOrdenCompra,
                    'id_producto' => $codigo,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                ]);
            }

            DB::commit();

            Alert::success('Éxito', 'Orden de Compra creada correctamente');
            return redirect()->route('ordencompra.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Error al crear la Orden de Compra: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ordenCompra = DB::selectOne(
            "SELECT oc.*, p.descripcion as proveedor, u.name as usuario,
                    s.descripcion as sucursal
             FROM ordenes_compra oc
             JOIN proveedores p ON oc.id_proveedor = p.id_proveedor
             LEFT JOIN users u ON oc.id_usuario = u.id
             LEFT JOIN sucursales s ON oc.id_sucursal = s.id_sucursal
             WHERE oc.id_orden_compra = ?",
            [$id]
        );

        if (!$ordenCompra) {
            Alert::error('Error', 'Orden de Compra no encontrada.');
            return redirect()->route('ordencompra.index');
        }

        $detalle = DB::select(
            "SELECT doc.*, prod.descripcion as producto
             FROM detalle_orden_compra doc
             JOIN productos prod ON doc.id_producto = prod.id_producto
             WHERE doc.id_orden_compra = ?",
            [$id]
        );

        return view('ordencompra.show', compact('ordenCompra', 'detalle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $ordenCompra = DB::selectOne(
            "SELECT * FROM ordenes_compra WHERE id_orden_compra = ?",
            [$id]
        );

        if (!$ordenCompra) {
            Alert::error('Error', 'Orden de Compra no encontrada.');
            return redirect()->route('ordencompra.index');
        }

        // Validar si puede ser editada
        if (in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA', 'PROCESADO'])) {
            Alert::warning('Advertencia', 'No se puede modificar una orden que está ' . $ordenCompra->estado);
            return redirect()->route('ordencompra.index');
        }

        // Cargar datos para el formulario
        $proveedores = DB::table('proveedores')
            ->pluck('descripcion', 'id_proveedor');

        $sucursales = $this->getSucursalesPermitidas(auth()->user(), $ordenCompra->id_sucursal);

        $usuario = auth()->user()->name;

        // Cargar detalle con información de impuestos
        $detalleOrdenCompra = DB::select(
            "SELECT doc.*, prod.descripcion as producto, t.porcentaje as tax_porcentaje
             FROM detalle_orden_compra doc
             JOIN productos prod ON doc.id_producto = prod.id_producto
             LEFT JOIN taxes t ON prod.id_tax = t.id_tax
             WHERE doc.id_orden_compra = ?",
            [$id]
        );

        return view('ordencompra.edit', compact(
            'ordenCompra',
            'detalleOrdenCompra',
            'proveedores',
            'sucursales',
            'usuario'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ordenCompra = DB::selectOne(
            "SELECT * FROM ordenes_compra WHERE id_orden_compra = ?",
            [$id]
        );

        if (!$ordenCompra) {
            Alert::error('Error', 'Orden de Compra no encontrada');
            return redirect()->route('ordencompra.index');
        }

        // Validar si puede ser editada
        if (in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA', 'PROCESADO'])) {
            Alert::warning('Advertencia', 'No se puede modificar una orden que está ' . $ordenCompra->estado);
            return redirect()->route('ordencompra.index');
        }

        $input = $request->validate([
            'id_proveedor' => 'required|exists:proveedores,id_proveedor',
            'fecha_orden' => 'required|date',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'observaciones' => 'nullable|string',
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

            // Inicializar totales
            $total = 0.0;
            $subtotal = 0.0;
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

            // Recalcular totales con IVA
            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = (float)str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);

                // Obtener información del producto
                $productoInfo = $productosInfo[(int)$codigo] ?? null;

                if ($productoInfo && $productoInfo->id_tax) {
                    $porcentaje_impuesto = $productoInfo->porcentaje;

                    // Calcular precio sin impuesto usando la fórmula paraguaya
                    $precio_sin_impuesto = $porcentaje_impuesto > 0
                        ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                        : $precio_con_iva;

                    // Calcular monto del impuesto
                    $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                    // Calcular subtotales por item
                    $subtotal_item = $precio_con_iva * $cantidad;
                    $gravada_item = $precio_sin_impuesto * $cantidad;
                    $impuesto_total_item = $monto_impuesto * $cantidad;
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
                    $total += $precio_con_iva * $cantidad;
                    $total_exento += $precio_con_iva * $cantidad;
                }
            }

            // Calcular subtotal y total de IVA
            $subtotal = $total_exento + $total_gravado_5 + $total_gravado_10;
            $total_iva = $total_iva_5 + $total_iva_10;
            $total = round($total, 2);

            // Actualizar cabecera con totales de IVA
            DB::update(
                'UPDATE ordenes_compra
                 SET id_proveedor = ?, fecha_orden = ?, subtotal = ?,
                     total_exento = ?, total_gravado_5 = ?, total_gravado_10 = ?,
                     total_iva_5 = ?, total_iva_10 = ?, total_iva = ?, total = ?,
                     id_sucursal = ?, observaciones = ?
                 WHERE id_orden_compra = ?',
                [
                    $input['id_proveedor'],
                    $input['fecha_orden'],
                    $subtotal,
                    $total_exento,
                    $total_gravado_5,
                    $total_gravado_10,
                    $total_iva_5,
                    $total_iva_10,
                    $total_iva,
                    $total,
                    $input['id_sucursal'],
                    $input['observaciones'] ?? null,
                    $id
                ]
            );

            // Obtener detalles existentes
            $detallesExistentes = DB::select(
                "SELECT id_detalle_orden, id_producto FROM detalle_orden_compra WHERE id_orden_compra = ?",
                [$id]
            );

            $productosFormulario = $input['codigo'];
            $detallesExistentesMap = [];
            foreach ($detallesExistentes as $det) {
                $detallesExistentesMap[$det->id_producto] = $det->id_detalle_orden;
            }

            // Eliminar productos que ya no están
            foreach ($detallesExistentes as $det) {
                if (!in_array($det->id_producto, $productosFormulario)) {
                    DB::delete('DELETE FROM detalle_orden_compra WHERE id_detalle_orden = ?', [$det->id_detalle_orden]);
                }
            }

            // Actualizar o insertar detalles
            foreach ($input['codigo'] as $i => $codigo) {
                $precio = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);
                $subtotal = $cantidad * (float)$precio;

                if (isset($detallesExistentesMap[$codigo])) {
                    // Actualizar existente
                    DB::update(
                        'UPDATE detalle_orden_compra
                         SET cantidad = ?, precio_unitario = ?, subtotal = ?
                         WHERE id_detalle_orden = ?',
                        [$cantidad, $precio, $subtotal, $detallesExistentesMap[$codigo]]
                    );
                } else {
                    // Insertar nuevo
                    DB::table('detalle_orden_compra')->insert([
                        'id_orden_compra' => $id,
                        'id_producto' => $codigo,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            DB::commit();

            Alert::success('Éxito', 'Orden de Compra actualizada correctamente');
            return redirect()->route('ordencompra.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Error al actualizar la Orden de Compra: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage (anular).
     */
    public function destroy(string $id)
    {
        $ordenCompra = DB::selectOne(
            "SELECT * FROM ordenes_compra WHERE id_orden_compra = ?",
            [$id]
        );

        if (!$ordenCompra) {
            Alert::error('Error', 'Orden de Compra no encontrada.');
            return redirect()->route('ordencompra.index');
        }

        // Validar si puede ser anulada
        if (in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA', 'PROCESADO'])) {
            Alert::error('Error', 'No se puede anular una orden que está ' . $ordenCompra->estado . '.');
            return redirect()->route('ordencompra.index');
        }

        try {
            DB::update('UPDATE ordenes_compra SET estado = ? WHERE id_orden_compra = ?', ['CANCELADA', $id]);
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
        }

        return redirect()->route('ordencompra.index');
    }

    /**
     * Buscar productos para el modal
     */
    public function buscarProducto(Request $request)
    {
        $query = $request->get('query');

        if ($query) {
            $productos = DB::select(
                "SELECT p.id_producto, p.descripcion, p.precio, t.porcentaje as tax_porcentaje
                 FROM productos p
                 LEFT JOIN taxes t ON p.id_tax = t.id_tax
                 WHERE p.activo = true
                 AND (CAST(p.id_producto AS TEXT) iLIKE ? OR CAST(p.descripcion AS TEXT) iLIKE ?)
                 LIMIT 20",
                ['%' . $query . '%', '%' . $query . '%']
            );
        } else {
            // Primeros 20 productos sin filtro
            $productos = DB::select(
                "SELECT p.id_producto, p.descripcion, p.precio, t.porcentaje as tax_porcentaje
                 FROM productos p
                 LEFT JOIN taxes t ON p.id_tax = t.id_tax
                 WHERE p.activo = true
                 LIMIT 20"
            );
        }

        return view('ordencompra.body_producto')->with('productos', $productos);
    }

    /**
     * Obtener órdenes de compra pendientes/aprobadas para Compras
     */
    public function getPendientes()
    {
        $ordenesCompra = DB::select(
            "SELECT oc.id_orden_compra, oc.fecha_orden, oc.total, oc.estado,
                    p.descripcion as proveedor
             FROM ordenes_compra oc
             JOIN proveedores p ON oc.id_proveedor = p.id_proveedor
             WHERE oc.estado IN ('PENDIENTE', 'APROBADA', 'PARCIAL')
             ORDER BY oc.fecha_orden DESC"
        );

        // Formatear datos
        $ordenesFormateadas = array_map(function ($oc) {
            return [
                'id_orden_compra' => $oc->id_orden_compra,
                'fecha_orden' => date('d/m/Y', strtotime($oc->fecha_orden)),
                'total' => number_format($oc->total, 0, ',', '.'),
                'estado' => $oc->estado,
                'proveedor' => $oc->proveedor,
            ];
        }, $ordenesCompra);

        return response()->json([
            'success' => true,
            'ordenesCompra' => $ordenesFormateadas
        ]);
    }

    // ==================== MÉTODOS DE IA - DESHABILITADOS ====================
    // Funcionalidad migrada a PedidosController
    // Los métodos createWithAi(), processAiPdf(), storeAi() y storeProviderAjax()
    // han sido eliminados. Ver PedidosController para la funcionalidad equivalente.
    // ====================================================================================
}
