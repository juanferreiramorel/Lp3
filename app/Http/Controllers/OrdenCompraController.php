<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use RealRashid\SweetAlert\Facades\Alert;
use App\Exports\OrdenCompraExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\AiOcrService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrdenCompraController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ordencompra index')->only(['index', 'show', 'getPendientes']);
        $this->middleware('permission:ordencompra create')->only(['create', 'store', 'buscarProducto', 'createWithAi', 'processAiPdf', 'storeAi', 'storeProviderAjax']);
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
                  ORDER BY oc.id_orden_compra DESC
                  LIMIT {$porPagina} OFFSET {$offset}";

        $ordenesCompra = DB::select($query, $params);

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        // Si es petición AJAX, solo retornar la tabla
        if ($request->ajax()) {
            return view('ordencompra.table', compact('ordenesCompra', 'page', 'totalPages', 'total'));
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

        return view('ordencompra.index', compact('ordenesCompra', 'page', 'totalPages', 'total', 'proveedores', 'sucursales', 'estados'));
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

        $sucursales = DB::table('sucursales')
            ->pluck('descripcion', 'id_sucursal');

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
                 WHERE (CAST(p.id_producto AS TEXT) iLIKE ? OR CAST(p.descripcion AS TEXT) iLIKE ?)
                 LIMIT 20",
                ['%' . $query . '%', '%' . $query . '%']
            );
        } else {
            // Primeros 20 productos sin filtro
            $productos = DB::select(
                "SELECT p.id_producto, p.descripcion, p.precio, t.porcentaje as tax_porcentaje
                 FROM productos p
                 LEFT JOIN taxes t ON p.id_tax = t.id_tax
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

    /**
     * Mostrar formulario para crear OC con IA
     */
    public function createWithAi()
    {
        $proveedores = DB::table('proveedores')
            ->pluck('descripcion', 'id_proveedor');
        $sucursales = $this->getSucursalesPermitidas();
        $usuario = auth()->user()->name;

        return view('ordencompra.create_ai', compact('proveedores', 'sucursales', 'usuario'));
    }

    /**
     * Procesar PDF subido con IA
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
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 422);
            }

            $aiData = $result['data'];

            if (!$aiService->validateResponse($aiData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Respuesta de IA inválida'
                ], 422);
            }

            // Buscar proveedor por RUC
            $proveedor = null;
            if (!empty($aiData['cabecera']['ruc'])) {
                $proveedor = DB::selectOne(
                    'SELECT * FROM proveedores WHERE ruc = ? LIMIT 1',
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

            // Guardar PDF en sesión para uso posterior
            $pdfContent = base64_encode(file_get_contents($file->getRealPath()));
            $pdfName = time() . '_' . $file->getClientOriginalName();
            session(['ai_pdf_content' => $pdfContent, 'ai_pdf_name' => $pdfName]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cabecera' => $aiData['cabecera'],
                    'proveedor' => $proveedor ? [
                        'id_proveedor' => $proveedor->id_proveedor,
                        'descripcion' => $proveedor->descripcion,
                        'ruc' => $proveedor->ruc,
                        'direccion' => $proveedor->direccion,
                        'telefono' => $proveedor->telefono,
                    ] : null,
                    'productos_encontrados' => $productosEncontrados,
                    'productos_no_encontrados' => $productosNoEncontrados,
                    'controles' => $aiData['controles'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI PDF Processing Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar orden de compra creada con IA
     */
    public function storeAi(Request $request)
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

            // Calcular totales (usar lógica existente del método store)
            $total = 0.0;
            $subtotal = 0.0;
            $total_exento = 0;
            $total_gravado_5 = 0;
            $total_gravado_10 = 0;
            $total_iva_5 = 0;
            $total_iva_10 = 0;

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

            foreach ($input['codigo'] as $i => $codigo) {
                $precio_con_iva = (float)str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);

                $productoInfo = $productosInfo[(int)$codigo] ?? null;

                if ($productoInfo && $productoInfo->id_tax) {
                    $porcentaje_impuesto = $productoInfo->porcentaje;
                    $precio_sin_impuesto = $porcentaje_impuesto > 0
                        ? $precio_con_iva / (1 + ($porcentaje_impuesto / 100))
                        : $precio_con_iva;
                    $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;
                    $subtotal_item = $precio_con_iva * $cantidad;
                    $gravada_item = $precio_sin_impuesto * $cantidad;
                    $impuesto_total_item = $monto_impuesto * $cantidad;
                    $total += $subtotal_item;

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
                    $total += $precio_con_iva * $cantidad;
                    $total_exento += $precio_con_iva * $cantidad;
                }
            }

            $subtotal = $total_exento + $total_gravado_5 + $total_gravado_10;
            $total_iva = $total_iva_5 + $total_iva_10;
            $total = round($total, 2);

            // Guardar PDF si existe en sesión
            $pdfPath = null;
            if (session()->has('ai_pdf_content') && session()->has('ai_pdf_name')) {
                $pdfContent = base64_decode(session('ai_pdf_content'));
                $pdfName = session('ai_pdf_name');
                $pdfPath = 'ordenes_compra/pdfs/' . $pdfName;
                Storage::disk('public')->put($pdfPath, $pdfContent);

                session()->forget(['ai_pdf_content', 'ai_pdf_name']);
            }

            // Insertar cabecera con pdf_path
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
                'pdf_path' => $pdfPath,
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

            Alert::success('Éxito', 'Orden de Compra creada correctamente con IA');
            return redirect()->route('ordencompra.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Error al crear la Orden de Compra: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Guardar nuevo proveedor vía AJAX
     */
    public function storeProviderAjax(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'descripcion' => 'required',
            'ruc' => 'nullable|string|max:20',
            'telefono' => 'required',
            'direccion' => 'nullable|string',
        ], [
            'descripcion.required' => 'Campo descripción obligatorio',
            'telefono.required' => 'Campo teléfono obligatorio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar duplicado por RUC
        if (!empty($input['ruc'])) {
            $existe = DB::selectOne(
                'SELECT * FROM proveedores WHERE ruc = ?',
                [$input['ruc']]
            );
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'errors' => ['ruc' => ['Ya existe un proveedor con este RUC.']]
                ], 422);
            }
        }

        $nuevoProveedor = DB::selectOne(
            'INSERT INTO proveedores (descripcion, ruc, direccion, telefono)
             VALUES (?, ?, ?, ?)
             RETURNING id_proveedor, descripcion, ruc',
            [
                $input['descripcion'],
                $input['ruc'] ?? null,
                $input['direccion'] ?? null,
                $input['telefono']
            ]
        );

        return response()->json([
            'success' => true,
            'proveedor' => [
                'id_proveedor' => $nuevoProveedor->id_proveedor,
                'descripcion' => $nuevoProveedor->descripcion,
                'ruc' => $nuevoProveedor->ruc
            ]
        ]);
    }
}
