<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class PedidosController extends Controller
{
    /** LISTADO */
    public function index(Request $request)
    {
        // Igual que ventas.index: incluimos usuario (JOIN users) y cliente
        $pedidos = DB::select(
            "SELECT p.*, CONCAT(c.clie_nombre,' ', c.clie_apellido) AS cliente, c.clie_ci,
                    u.name AS usuario
               FROM pedidos p
               JOIN clientes c ON p.id_cliente = c.id_cliente
            JOIN users u   ON p.id_usuario = u.id
           ORDER BY p.fecha_pedido DESC"
        );

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($pedidos);            // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($pedidos, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $pedidos = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        return view('pedidos.index')->with('pedidos', $pedidos);
    }

    /** CREAR */
    public function create()
    {
        // Select de clientes (mismo estilo que ventas)
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre,' ', clie_apellido) AS cliente")
            ->pluck('cliente', 'id_cliente');

        // Sucursales (si existe la tabla)
        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');

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
                'codigo.required' => 'Debe agregar al menos un producto.',
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

            // Insertar cabecera de pedido
            $idPedido = DB::table('pedidos')->insertGetId([
                'id_cliente'   => $input['id_cliente'],
                'fecha_pedido' => $input['fecha_pedido'],
                'total_pedido' => $total,
                'id_sucursal'  => $input['id_sucursal'] ?? null,
                'estado'       => $input['estado'] ?? 'PENDIENTE',
                'id_usuario'   => $user_id, // NUEVO: guarda el usuario que registra
            ], 'id_pedido');

            // Insertar detalle del pedido (precio -> precio_unitario)
            foreach ($input['codigo'] as $i => $codigo) {
                $monto = str_replace('.', '', $input['precio'][$i] ?? '0');
                DB::table('detalle_pedido')->insert([
                    'id_pedido'       => $idPedido,
                    'id_producto'     => (int)$codigo,
                    'cantidad'        => (int)($input['cantidad'][$i] ?? 0),
                    'precio_unitario' => (float)$monto,
                ]);
            }

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

        // Detalle + productos
        $detalle = DB::select(
            "SELECT d.*, pr.descripcion
               FROM detalle_pedido d
               JOIN productos pr ON pr.id_producto = d.id_producto
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

        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');

        $usuario = auth()->user()->name ?? '';

        $detalle = DB::select(
            "SELECT d.*, pr.descripcion
               FROM detalle_pedido d
               JOIN productos pr ON pr.id_producto = d.id_producto
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
                'estado'       => 'nullable|in:PENDIENTE,PROCESADO,CANCELADO',

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

            // Recalcular total
            $total = 0;
            foreach ($input['codigo'] as $i => $codigo) {
                $monto = str_replace('.', '', $input['precio'][$i] ?? '0');
                $cantidad = (int)($input['cantidad'][$i] ?? 0);
                $total += ($cantidad * (float)$monto);
            }

            // Actualizar cabecera
            DB::update(
                'UPDATE pedidos SET fecha_pedido = ?, total_pedido = ?, id_sucursal = ?, estado = ? WHERE id_pedido = ?',
                [
                    $input['fecha_pedido'],
                    $total,
                    $input['id_sucursal'] ?? null,
                    $input['estado'] ?? 'PENDIENTE',
                    $id
                ]
            );

            // Actualizar o insertar detalles
            foreach ($input['codigo'] as $i => $codigo) {
                $monto = str_replace('.', '', $input['precio'][$i] ?? '0');

                $detalle = DB::selectOne(
                    'SELECT * FROM detalle_pedido WHERE id_pedido = ? AND id_producto = ?',
                    [$id, (int)$codigo]
                );

                if (!empty($detalle)) {
                    // Actualizar detalle existente
                    DB::update(
                        'UPDATE detalle_pedido SET cantidad = ?, precio_unitario = ? WHERE id_pedido = ? AND id_producto = ?',
                        [
                            (int)($input['cantidad'][$i] ?? 0),
                            (float)$monto,
                            $id,
                            (int)$codigo
                        ]
                    );
                } else {
                    // Insertar nuevo detalle
                    DB::table('detalle_pedido')->insert([
                        'id_pedido'       => $id,
                        'id_producto'     => (int)$codigo,
                        'cantidad'        => (int)($input['cantidad'][$i] ?? 0),
                        'precio_unitario' => (float)$monto,
                    ]);
                }
            }

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
            Alert::error('Error', 'Pedido no encontrado');
            return redirect()->route('pedidos.index');
        }

        DB::beginTransaction();
        try {
            // No borramos físico: marcamos estado CANCELADO
            DB::update('UPDATE pedidos SET estado = ? WHERE id_pedido = ?', ['CANCELADO', $id]);
            DB::commit();
        } catch (\Exception $e) {
            Log::info('Error al cancelar el pedido: ' . $e->getMessage());
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }

        Alert::success('Éxito', 'Pedido cancelado.');
        return redirect()->route('pedidos.index');
    }

    /** BUSCADOR DE PRODUCTO */
    public function buscarProducto(Request $request)
    {
        $cod_suc = $request->get('sucursal'); // id_sucursal actual del form
        $buscar  = $request->get('q');        // texto de búsqueda

        if (!empty($buscar)) {
            $buscar = mb_strtolower($buscar, 'UTF-8');
            $productos = DB::select(
                "SELECT p.*, s.cantidad, s.id_sucursal
                   FROM productos p
                   JOIN stocks s ON p.id_producto = s.id_producto
                  WHERE s.id_sucursal = ?
                    AND (LOWER(p.descripcion) LIKE ? OR CAST(p.id_producto AS TEXT) LIKE ?)
                  LIMIT 50",
                [$cod_suc, "%{$buscar}%", "%{$buscar}%"]
            );
        } else {
            // primeros 20 sin filtro
            $productos = DB::select(
                "SELECT p.*, s.cantidad, s.id_sucursal
                   FROM productos p
                   JOIN stocks s ON p.id_producto = s.id_producto
                  WHERE s.id_sucursal = ?
                  LIMIT 20",
                [$cod_suc]
            );
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
}