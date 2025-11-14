<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;
use Luecano\NumeroALetras\NumeroALetras;

class ReciboSimpleController extends AppBaseController
{
    public function __construct()
    {
        // validar que el usuario este autenticado
        $this->middleware('auth');
        // validar permisos para cada accion
        $this->middleware('permission:recibossimples index')->only(['index', 'show', 'imprimir']);
        $this->middleware('permission:recibossimples create')->only(['create', 'store']);
        $this->middleware('permission:recibossimples edit')->only(['edit', 'update']);
        $this->middleware('permission:recibossimples destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND r.fecha_recibo BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND r.fecha_recibo >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND r.fecha_recibo <= '{$fecha_hasta}'";
        }

        // FILTRO POR CLIENTE
        if (!empty($input['id_cliente'])) {
            $sql_filtros .= " AND r.id_cliente = " . (int)$input['id_cliente'];
        }

        // FILTRO POR ESTADO
        if (!empty($input['estado'])) {
            $sql_filtros .= " AND r.estado = '" . $input['estado'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND r.id_sucursal = " . (int)$input['id_sucursal'];
        }

        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $sql_busqueda = 'WHERE 1=1';

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql_busqueda = "WHERE r.id_recibo = " . (int)$searchConfig['term'];
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

                $sql_busqueda = "WHERE (CAST(r.id_recibo AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(c.clie_nombre AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(c.clie_apellido AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(c.clie_ci AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR CAST(r.fecha_recibo AS TEXT) ILIKE '%{$buscarFecha}%'
                    OR TO_CHAR(r.fecha_recibo, 'DD/MM/YYYY') ILIKE '%{$buscarTerm}%'
                    OR r.estado ILIKE '%{$buscarTerm}%'
                    OR CAST(r.total AS TEXT) ILIKE '%{$buscarMonto}%'
                    OR TO_CHAR(r.total, 'FM999G999G999G999G999') ILIKE '%{$buscarTerm}%'
                    OR users.name ILIKE '%{$buscarTerm}%')";
            }
        }

        // Agregar filtros a la búsqueda
        $sql_busqueda .= $sql_filtros;

        $perPage = 20;
        $page = $request->get('page', 1);

        // Consulta para contar los resultados
        $totalCount = DB::selectOne(
            "SELECT COUNT(*) as total
            FROM recibos_simples r
                JOIN clientes c ON r.id_cliente = c.id_cliente
                JOIN users ON r.user_id = users.id
            {$sql_busqueda}"
        )->total;

        // Consulta principal con paginación
        $recibos = DB::select(
            "SELECT r.id_recibo,
                r.fecha_recibo,
                CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente,
                c.clie_ci,
                r.total,
                r.estado,
                users.name as usuario,
                s.descripcion as sucursal
            FROM recibos_simples r
                JOIN clientes c ON r.id_cliente = c.id_cliente
                JOIN users ON r.user_id = users.id
                JOIN sucursales s ON r.id_sucursal = s.id_sucursal
            {$sql_busqueda}
            ORDER BY r.id_recibo DESC
            LIMIT {$perPage} OFFSET " . ($page - 1) * $perPage
        );

        // Crear el paginador
        $recibos = new LengthAwarePaginator(
            $recibos,
            $totalCount,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Obtener datos para los filtros
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre, ' ', clie_apellido) as cliente")
            ->orderBy('clie_nombre')
            ->pluck('cliente', 'id_cliente');

        $sucursales = DB::table('sucursales')
            ->pluck('descripcion', 'id_sucursal');

        $estados = [
            'ACTIVO' => 'ACTIVO',
            'ANULADO' => 'ANULADO'
        ];

        return view('recibossimples.index')
            ->with('recibos', $recibos)
            ->with('clientes', $clientes)
            ->with('sucursales', $sucursales)
            ->with('estados', $estados)
            ->with('filtros', $input);
    }

    public function create()
    {
        // Obtener clientes activos
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre, ' ', clie_apellido) as cliente")
            ->orderBy('clie_nombre')
            ->pluck('cliente', 'id_cliente');

        // Obtener usuario actual
        $usuario = auth()->user()->name;

        // Obtener sucursales activas
        $sucursales = DB::table('sucursales')
            ->pluck('descripcion', 'id_sucursal');

        // Obtener métodos de pago
        $metodos_pago = DB::table('metodo_pagos')
            ->where('estado', True)
            ->pluck('descripcion', 'id_metodo_pago');

        // Obtener departamentos
        $departamentos = DB::table('departamentos')
            ->pluck('descripcion', 'id_departamento');

        return view('recibossimples.create')
            ->with('clientes', $clientes)
            ->with('usuario', $usuario)
            ->with('sucursales', $sucursales)
            ->with('metodos_pago', $metodos_pago)
            ->with('departamentos', $departamentos);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // Validaciones
        $validacion = Validator::make(
            $input,
            [
                'id_cliente' => 'required|exists:clientes,id_cliente',
                'fecha_recibo' => 'required|date',
                'user_id' => 'required|exists:users,id',
                'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            ],
            [
                'id_cliente.required' => 'El campo cliente es obligatorio.',
                'id_cliente.exists' => 'El cliente seleccionado no es válido.',
                'fecha_recibo.required' => 'El campo fecha de recibo es obligatorio.',
                'fecha_recibo.date' => 'El campo fecha de recibo debe ser una fecha válida.',
                'user_id.required' => 'El campo usuario es obligatorio.',
                'user_id.exists' => 'El usuario seleccionado no es válido.',
                'id_sucursal.required' => 'El campo sucursal es obligatorio.',
                'id_sucursal.exists' => 'La sucursal seleccionada no es válida.',
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Validar fecha de recibo no sea mayor a la fecha actual
        if (Carbon::parse($input['fecha_recibo'])->format('Y-m-d') > Carbon::now()->format('Y-m-d')) {
            Alert::toast('La fecha del recibo no puede ser mayor a la fecha actual', 'error');
            return redirect()->back()->withInput($input);
        }

        // Recuperar el usuario en sesión
        $user_id = auth()->user()->id;

        // Limpiar el formato del total (quitar separadores de miles)
        $total = isset($input['total']) ? str_replace('.', '', $input['total']) : 0;

        // Transacción para guardar recibo, detalles y cobros
        DB::beginTransaction();
        try {
            // Insertar cabecera del recibo
            $id_recibo = DB::table('recibos_simples')->insertGetId([
                'id_cliente' => $input['id_cliente'],
                'id_sucursal' => $input['id_sucursal'],
                'user_id' => $user_id,
                'fecha_recibo' => $input['fecha_recibo'],
                'total' => $total,
                'estado' => 'ACTIVO',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_recibo');

            // Insertar detalle del recibo (productos)
            $subtotal_calculado = 0;
            if ($request->has('codigo') && is_array($input['codigo'])) {
                foreach ($input['codigo'] as $key => $codigo) {
                    // Validar que existan todos los elementos necesarios
                    if (!isset($input['precio'][$key]) || !isset($input['cantidad'][$key]) || !isset($input['producto'][$key])) {
                        continue; // Saltar este producto si faltan datos
                    }

                    // Limpiar formato de precio
                    $precio = str_replace('.', '', $input['precio'][$key]);
                    $cantidad = $input['cantidad'][$key];
                    $subtotal = $precio * $cantidad;
                    $subtotal_calculado += $subtotal;

                    // Insertar en detalle_recibos_simples
                    DB::insert(
                        'INSERT INTO detalle_recibos_simples (id_recibo, id_producto, concepto, cantidad, precio_unitario, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $id_recibo,
                            $codigo,
                            $input['producto'][$key],
                            $cantidad,
                            $precio,
                            $subtotal
                        ]
                    );
                }

                // Actualizar el total en la tabla recibos_simples
                DB::update('UPDATE recibos_simples SET total = ? WHERE id_recibo = ?', [
                    $subtotal_calculado,
                    $id_recibo
                ]);
            }

            // Insertar múltiples cobros/pagos
            if ($request->has('metodo_pago') && is_array($input['metodo_pago'])) {
                foreach ($input['metodo_pago'] as $key => $id_metodo) {
                    // Validar que exista el importe
                    if (!isset($input['importe_pago'][$key])) {
                        continue; // Saltar este pago si falta el importe
                    }

                    $importe_pago = str_replace('.', '', $input['importe_pago'][$key]);

                    DB::insert(
                        'INSERT INTO cobros_recibos_simples (id_recibo, id_metodo_pago, importe, nro_voucher, observacion, fecha_cobro, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [
                            $id_recibo,
                            $id_metodo,
                            $importe_pago,
                            $input['nro_voucher'][$key] ?? null,
                            $input['observacion_pago'][$key] ?? null,
                            $input['fecha_recibo'],
                            now()
                        ]
                    );
                }
            }

            // Confirmar transacción
            DB::commit();
        } catch (\Exception $e) {
            Log::info('Error al registrar el recibo: ' . $e->getMessage());
            DB::rollback();
            Alert::toast('Error al registrar el recibo: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput($request->all());
        }

        Alert::toast('Recibo registrado exitosamente.', 'success');
        return redirect()->route('recibossimples.index');
    }

    public function buscarProducto(Request $request)
    {
        // Consulta personalizada para el buscador de productos con stock en la sucursal seleccionada
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
            $productos = DB::select(
                "SELECT productos.*, stocks.cantidad, stocks.id_sucursal
                FROM productos
                    JOIN stocks ON productos.id_producto = stocks.id_producto
                WHERE stocks.id_sucursal = ?
                LIMIT 20",
                [$cod_suc]
            );
        }

        // Preparar y devolver la vista con productos encontrados
        return view('recibossimples.modal_producto_resultado', compact('productos'))->render();
    }

    public function show($id)
    {
        // Obtener la cabecera del recibo
        $recibo = DB::selectOne(
            "SELECT r.*,
                CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente,
                c.clie_ci,
                c.clie_telefono,
                c.clie_direccion,
                users.name as usuario,
                s.descripcion as sucursal
            FROM recibos_simples r
                JOIN clientes c ON r.id_cliente = c.id_cliente
                JOIN users ON r.user_id = users.id
                JOIN sucursales s ON r.id_sucursal = s.id_sucursal
            WHERE r.id_recibo = ?",
            [$id]
        );

        if (empty($recibo)) {
            Alert::toast('Recibo no encontrado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Obtener los detalles del recibo
        $detalle_recibo = DB::select(
            "SELECT * FROM detalle_recibos_simples WHERE id_recibo = ?",
            [$id]
        );

        // Obtener información de los cobros (múltiples formas de pago)
        $cobros = DB::select(
            "SELECT cr.*, mp.descripcion as metodo_pago
            FROM cobros_recibos_simples cr
                JOIN metodo_pagos mp ON cr.id_metodo_pago = mp.id_metodo_pago
            WHERE cr.id_recibo = ?
            ORDER BY cr.id_cobro_recibo",
            [$id]
        );

        return view('recibossimples.show')
            ->with('recibo', $recibo)
            ->with('detalle_recibo', $detalle_recibo)
            ->with('cobros', $cobros);
    }

    public function edit($id)
    {
        $recibo = DB::selectOne('SELECT * FROM recibos_simples WHERE id_recibo = ?', [$id]);

        if (empty($recibo)) {
            Alert::toast('Recibo no encontrado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Validar que el recibo esté activo
        if ($recibo->estado === 'ANULADO') {
            Alert::toast('No se puede editar un recibo anulado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Obtener clientes
        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, CONCAT(clie_nombre, ' ', clie_apellido) as cliente")
            ->pluck('cliente', 'id_cliente');

        // Usuario actual
        $usuario = auth()->user()->name;

        // Sucursales
        $sucursales = DB::table('sucursales')
            ->pluck('descripcion', 'id_sucursal');

        // Métodos de pago
        $metodos_pago = DB::table('metodo_pagos')
            ->where('estado', True)
            ->pluck('descripcion', 'id_metodo_pago');

        // Detalles del recibo
        $detalle_recibo = DB::select(
            "SELECT * FROM detalle_recibos_simples WHERE id_recibo = ?",
            [$id]
        );

        // Información de los cobros (múltiples formas de pago)
        $cobros = DB::select(
            "SELECT * FROM cobros_recibos_simples WHERE id_recibo = ?",
            [$id]
        );

        // Obtener departamentos
        $departamentos = DB::table('departamentos')
            ->pluck('descripcion', 'id_departamento');

        return view('recibossimples.edit')
            ->with('recibo', $recibo)
            ->with('clientes', $clientes)
            ->with('usuario', $usuario)
            ->with('sucursales', $sucursales)
            ->with('metodos_pago', $metodos_pago)
            ->with('detalle_recibo', $detalle_recibo)
            ->with('cobros', $cobros)
            ->with('departamentos', $departamentos);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        // Validar que el recibo existe
        $recibo = DB::selectOne('SELECT * FROM recibos_simples WHERE id_recibo = ?', [$id]);

        if (empty($recibo)) {
            Alert::toast('Recibo no encontrado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Validar que el recibo esté activo
        if ($recibo->estado === 'ANULADO') {
            Alert::toast('No se puede editar un recibo anulado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Validaciones
        $validacion = Validator::make(
            $input,
            [
                'id_cliente' => 'required|exists:clientes,id_cliente',
                'fecha_recibo' => 'required|date',
                'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            ],
            [
                'id_cliente.required' => 'El campo cliente es obligatorio.',
                'id_cliente.exists' => 'El cliente seleccionado no es válido.',
                'fecha_recibo.required' => 'El campo fecha de recibo es obligatorio.',
                'fecha_recibo.date' => 'El campo fecha de recibo debe ser una fecha válida.',
                'id_sucursal.required' => 'El campo sucursal es obligatorio.',
                'id_sucursal.exists' => 'La sucursal seleccionada no es válida.',
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Validar fecha
        if (Carbon::parse($input['fecha_recibo'])->format('Y-m-d') > Carbon::now()->format('Y-m-d')) {
            Alert::toast('La fecha del recibo no puede ser mayor a la fecha actual', 'error');
            return redirect()->back()->withInput($input);
        }

        // Transacción para actualizar
        DB::beginTransaction();
        try {
            // Actualizar cabecera del recibo
            DB::update(
                'UPDATE recibos_simples
                SET id_cliente = ?, id_sucursal = ?, fecha_recibo = ?, updated_at = ?
                WHERE id_recibo = ?',
                [
                    $input['id_cliente'],
                    $input['id_sucursal'],
                    $input['fecha_recibo'],
                    now(),
                    $id
                ]
            );

            // Eliminar detalles anteriores
            DB::delete('DELETE FROM detalle_recibos_simples WHERE id_recibo = ?', [$id]);

            // Insertar nuevos detalles (productos)
            $subtotal_calculado = 0;
            if ($request->has('codigo') && is_array($input['codigo'])) {
                foreach ($input['codigo'] as $key => $codigo) {
                    // Validar que existan todos los elementos necesarios
                    if (!isset($input['precio'][$key]) || !isset($input['cantidad'][$key]) || !isset($input['producto'][$key])) {
                        continue; // Saltar este producto si faltan datos
                    }

                    $precio = str_replace('.', '', $input['precio'][$key]);
                    $cantidad = $input['cantidad'][$key];
                    $subtotal = $precio * $cantidad;
                    $subtotal_calculado += $subtotal;

                    DB::insert(
                        'INSERT INTO detalle_recibos_simples (id_recibo, id_producto, concepto, cantidad, precio_unitario, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $id,
                            $codigo,
                            $input['producto'][$key],
                            $cantidad,
                            $precio,
                            $subtotal
                        ]
                    );
                }

                // Actualizar total
                DB::update('UPDATE recibos_simples SET total = ? WHERE id_recibo = ?', [
                    $subtotal_calculado,
                    $id
                ]);
            }

            // Eliminar cobros anteriores
            DB::delete('DELETE FROM cobros_recibos_simples WHERE id_recibo = ?', [$id]);

            // Insertar nuevos cobros (múltiples formas de pago)
            if ($request->has('metodo_pago') && is_array($input['metodo_pago'])) {
                foreach ($input['metodo_pago'] as $key => $id_metodo) {
                    // Validar que exista el importe
                    if (!isset($input['importe_pago'][$key])) {
                        continue; // Saltar este pago si falta el importe
                    }

                    $importe_pago = str_replace('.', '', $input['importe_pago'][$key]);

                    DB::insert(
                        'INSERT INTO cobros_recibos_simples (id_recibo, id_metodo_pago, importe, nro_voucher, observacion, fecha_cobro, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [
                            $id,
                            $id_metodo,
                            $importe_pago,
                            $input['nro_voucher'][$key] ?? null,
                            $input['observacion_pago'][$key] ?? null,
                            $input['fecha_recibo'],
                            now()
                        ]
                    );
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            Log::info('Error al actualizar el recibo: ' . $e->getMessage());
            DB::rollback();
            Alert::toast('Error al actualizar el recibo: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput($request->all());
        }

        Alert::toast('Recibo actualizado exitosamente.', 'success');
        return redirect()->route('recibossimples.index');
    }

    public function destroy($id)
    {
        $recibo = DB::selectOne('SELECT * FROM recibos_simples WHERE id_recibo = ?', [$id]);

        if (empty($recibo)) {
            Alert::toast('Recibo no encontrado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Validar que no esté ya anulado
        if ($recibo->estado === 'ANULADO') {
            Alert::toast('El recibo ya está anulado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Cambiar estado a ANULADO (soft delete)
        DB::update('UPDATE recibos_simples SET estado = ?, updated_at = ? WHERE id_recibo = ?', [
            'ANULADO',
            now(),
            $id
        ]);

        Alert::toast('Recibo anulado exitosamente.', 'success');
        return redirect()->route('recibossimples.index');
    }

    public function imprimir($id)
    {
        // Obtener datos del recibo con información de la empresa
        $recibo = DB::selectOne(
            "SELECT r.*,
                CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente,
                c.clie_ci,
                c.clie_telefono,
                c.clie_direccion,
                users.name as usuario,
                s.descripcion as sucursal,
                s.direccion as sucursal_direccion,
                s.telefono as sucursal_telefono,
                e.razon_social as empresa_razon_social,
                e.nombre_comercial as empresa_nombre,
                e.ruc as empresa_ruc,
                e.direccion as empresa_direccion,
                e.telefono as empresa_telefono,
                e.email as empresa_email,
                e.logo as empresa_logo
            FROM recibos_simples r
                JOIN clientes c ON r.id_cliente = c.id_cliente
                JOIN users ON r.user_id = users.id
                JOIN sucursales s ON r.id_sucursal = s.id_sucursal
                LEFT JOIN empresas e ON s.id_empresa = e.id_empresa
            WHERE r.id_recibo = ?",
            [$id]
        );

        if (empty($recibo)) {
            Alert::toast('Recibo no encontrado', 'error');
            return redirect()->route('recibossimples.index');
        }

        // Obtener detalles
        $detalle_recibo = DB::select(
            "SELECT * FROM detalle_recibos_simples WHERE id_recibo = ?",
            [$id]
        );

        // Obtener información de los cobros (múltiples formas de pago)
        $cobros = DB::select(
            "SELECT cr.*, mp.descripcion as metodo_pago
            FROM cobros_recibos_simples cr
                JOIN metodo_pagos mp ON cr.id_metodo_pago = mp.id_metodo_pago
            WHERE cr.id_recibo = ?
            ORDER BY cr.id_cobro_recibo",
            [$id]
        );

        // Convertir número a letras
        $formatter = new NumeroALetras();
        $total_letras = $formatter->toWords($recibo->total);

        return view('recibossimples.recibo')
            ->with('recibo', $recibo)
            ->with('detalle_recibo', $detalle_recibo)
            ->with('cobros', $cobros)
            ->with('total_letras', $total_letras);
    }
}
