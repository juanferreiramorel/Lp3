<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use RealRashid\SweetAlert\Facades\Alert;

class OrdenCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->buscar;
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // Query base
        $queryBase = "FROM ordenes_compra oc
                      JOIN proveedores p ON oc.id_proveedor = p.id_proveedor
                      LEFT JOIN users u ON oc.id_usuario = u.id
                      LEFT JOIN sucursales s ON oc.id_sucursal = s.id_sucursal";

        // Condiciones de búsqueda
        $where = "WHERE 1=1";
        $params = [];

        if ($buscar) {
            $where .= " AND (
                cast(oc.id_orden_compra as text) ILIKE ? OR
                p.descripcion ILIKE ? OR
                cast(oc.fecha_orden as text) ILIKE ? OR
                TO_CHAR(oc.fecha_orden, 'DD/MM/YYYY') ILIKE ? OR
                cast(oc.total_orden as text) ILIKE ? OR
                TO_CHAR(oc.total_orden, 'FM999G999G999G999G999') ILIKE ? OR
                u.name ILIKE ? OR
                oc.estado ILIKE ? OR
                s.descripcion ILIKE ? OR
                cast(oc.fecha_entrega_esperada as text) ILIKE ? OR
                TO_CHAR(oc.fecha_entrega_esperada, 'DD/MM/YYYY') ILIKE ?
            )";
            $searchTerm = "%{$buscar}%";
            $params = array_fill(0, 11, $searchTerm);
        }

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$where}";
        $totalResult = DB::select($totalQuery, $params);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT oc.id_orden_compra, oc.fecha_orden, oc.total_orden, oc.estado,
                         oc.fecha_entrega_esperada, oc.observaciones,
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

        return view('ordencompra.index', compact('ordenesCompra', 'page', 'totalPages', 'total'));
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
        $sucursales = DB::table('sucursales')
            ->pluck('descripcion', 'id_sucursal');

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
            'fecha_entrega_esperada' => 'nullable|date',
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

            // Calcular total desde el detalle
            $total = 0;
            foreach ($input['codigo'] as $i => $codigo) {
                $monto = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);
                $total += ($cantidad * (float)$monto);
            }

            // Insertar cabecera - especificar la columna de secuencia correcta
            $idOrdenCompra = DB::table('ordenes_compra')->insertGetId([
                'id_proveedor' => $input['id_proveedor'],
                'fecha_orden' => $input['fecha_orden'],
                'total_orden' => $total,
                'id_sucursal' => $input['id_sucursal'],
                'estado' => 'PENDIENTE',
                'id_usuario' => auth()->user()->id,
                'observaciones' => $input['observaciones'] ?? null,
                'fecha_entrega_esperada' => $input['fecha_entrega_esperada'] ?? null,
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
            Alert::error('Error', 'Orden de Compra no encontrada');
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
            Alert::error('Error', 'Orden de Compra no encontrada');
            return redirect()->route('ordencompra.index');
        }

        // Validar si puede ser editada
        if (in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA'])) {
            Alert::warning('Advertencia', 'No se puede modificar una orden que está ' . $ordenCompra->estado);
            return redirect()->route('ordencompra.index');
        }

        // Cargar datos para el formulario
        $proveedores = DB::table('proveedores')
            ->pluck('descripcion', 'id_proveedor');

        $sucursales = DB::table('sucursales')
            ->pluck('descripcion', 'id_sucursal');

        $usuario = auth()->user()->name;

        // Cargar detalle
        $detalleOrdenCompra = DB::select(
            "SELECT doc.*, prod.descripcion as producto
             FROM detalle_orden_compra doc
             JOIN productos prod ON doc.id_producto = prod.id_producto
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
        if (in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA'])) {
            Alert::warning('Advertencia', 'No se puede modificar una orden que está ' . $ordenCompra->estado);
            return redirect()->route('ordencompra.index');
        }

        $input = $request->validate([
            'id_proveedor' => 'required|exists:proveedores,id_proveedor',
            'fecha_orden' => 'required|date',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'fecha_entrega_esperada' => 'nullable|date',
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

            // Recalcular total
            $total = 0;
            foreach ($input['codigo'] as $i => $codigo) {
                $monto = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);
                $total += ($cantidad * (float)$monto);
            }

            // Actualizar cabecera
            DB::update(
                'UPDATE ordenes_compra
                 SET id_proveedor = ?, fecha_orden = ?, total_orden = ?, id_sucursal = ?,
                     observaciones = ?, fecha_entrega_esperada = ?
                 WHERE id_orden_compra = ?',
                [
                    $input['id_proveedor'],
                    $input['fecha_orden'],
                    $total,
                    $input['id_sucursal'],
                    $input['observaciones'] ?? null,
                    $input['fecha_entrega_esperada'] ?? null,
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
            Alert::error('Error', 'Orden de Compra no encontrada');
            return redirect()->route('ordencompra.index');
        }

        // Validar si puede ser anulada
        if (in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA'])) {
            Alert::warning('Advertencia', 'No se puede anular una orden que está ' . $ordenCompra->estado);
            return redirect()->route('ordencompra.index');
        }

        try {
            DB::update('UPDATE ordenes_compra SET estado = ? WHERE id_orden_compra = ?', ['CANCELADA', $id]);
            Alert::success('Éxito', 'Orden de Compra anulada correctamente');
        } catch (\Exception $e) {
            Alert::error('Error', 'Error al anular la Orden de Compra: ' . $e->getMessage());
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
                "SELECT id_producto, descripcion, precio
                 FROM productos
                 WHERE (CAST(id_producto AS TEXT) iLIKE ? OR CAST(descripcion AS TEXT) iLIKE ?)
                 LIMIT 20",
                ['%' . $query . '%', '%' . $query . '%']
            );
        } else {
            // Primeros 20 productos sin filtro
            $productos = DB::select(
                "SELECT id_producto, descripcion, precio
                 FROM productos
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
            "SELECT oc.id_orden_compra, oc.fecha_orden, oc.total_orden, oc.estado,
                    oc.fecha_entrega_esperada, p.descripcion as proveedor
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
                'total_orden' => number_format($oc->total_orden, 0, ',', '.'),
                'estado' => $oc->estado,
                'proveedor' => $oc->proveedor,
                'fecha_entrega_esperada' => $oc->fecha_entrega_esperada ? date('d/m/Y', strtotime($oc->fecha_entrega_esperada)) : '',
            ];
        }, $ordenesCompra);

        return response()->json([
            'success' => true,
            'ordenesCompra' => $ordenesFormateadas
        ]);
    }
}
