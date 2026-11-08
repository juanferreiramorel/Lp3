<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;
use Carbon\Carbon;
use Luecano\NumeroALetras\NumeroALetras;
use Illuminate\Pagination\LengthAwarePaginator;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Timbrado;
use App\Models\TipoTimbrado;
use App\Models\NotaDebito;
use App\Models\DetalleNotaDebito;
use App\Models\Venta;

class NotaDebitoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:notas_debito index')->only(['index', 'show', 'pdf']);
        $this->middleware('permission:notas_debito create')->only(['create', 'store', 'buscarProductosJson', 'buscarVentasJson']);
        $this->middleware('permission:notas_debito edit')->only(['edit', 'update']);
        $this->middleware('permission:notas_debito destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $sql_filtros .= " AND nd.fecha_emision BETWEEN '{$input['fecha_desde']}' AND '{$input['fecha_hasta']}'";
        } elseif (!empty($input['fecha_desde'])) {
            $sql_filtros .= " AND nd.fecha_emision >= '{$input['fecha_desde']}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $sql_filtros .= " AND nd.fecha_emision <= '{$input['fecha_hasta']}'";
        }

        // FILTRO POR CLIENTE
        if (!empty($input['id_cliente'])) {
            $sql_filtros .= " AND nd.id_cliente = " . (int)$input['id_cliente'];
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND nd.id_sucursal = " . (int)$input['id_sucursal'];
        }

        // FILTRO POR ESTADO
        if (!empty($input['estado'])) {
            $sql_filtros .= " AND nd.estado = '" . $input['estado'] . "'";
        }

        $searchMode = $request->get('search_mode', 'all');
        $buscar = $request->get('buscar');
        $sql_busqueda = 'WHERE 1=1';

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                $sql_busqueda .= " AND nd.id_nota_debito = " . (int)$searchConfig['term'];
            } else {
                $buscarTerm = $searchConfig['term'];

                // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd
                $buscarFecha = $buscarTerm;
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }

                // Limpiar separadores de miles para búsqueda de montos
                $buscarMonto = str_replace('.', '', $buscarTerm);

                $sql_busqueda .= " AND (cast(nd.nro_nota as text) ILIKE '%{$buscarTerm}%' 
                                   OR cast(c.clie_nombre as text) ILIKE '%{$buscarTerm}%' 
                                   OR cast(c.clie_apellido as text) ILIKE '%{$buscarTerm}%'
                                   OR cast(c.clie_ci as text) ILIKE '%{$buscarTerm}%'
                                   OR cast(nd.fecha_emision as text) ILIKE '%{$buscarFecha}%'
                                   OR TO_CHAR(nd.fecha_emision, 'DD/MM/YYYY') ILIKE '%{$buscarTerm}%'
                                   OR cast(nd.total as text) ILIKE '%{$buscarMonto}%'
                                   OR TO_CHAR(nd.total, 'FM999G999G999G999G999') ILIKE '%{$buscarTerm}%'
                                   OR users.name ILIKE '%{$buscarTerm}%')";
            }
        }

        $idsPermitidos = $this->getSucursalesPermitidasIds();
        $sqlSucursales = '';
        if (!empty($idsPermitidos)) {
            $sqlSucursales = ' AND nd.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        }

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'nd.id_nota_debito', 'first' => 'desc'],
            'ci' => ['label' => 'Ci/Ruc', 'sort' => 'c.clie_ci', 'tiebreaker' => 'nd.id_nota_debito'],
            'cliente' => ['label' => 'Cliente', 'sort' => "concat(c.clie_nombre,' ', c.clie_apellido)", 'tiebreaker' => 'nd.id_nota_debito'],
            'sucursal' => ['label' => 'Sucursal', 'sort' => 's.descripcion', 'tiebreaker' => 'nd.id_nota_debito'],
            'fecha_emision' => ['label' => 'Fecha Emisión', 'sort' => 'nd.fecha_emision', 'tiebreaker' => 'nd.id_nota_debito', 'first' => 'desc'],
            'nro_nota' => ['label' => 'Nro Nota', 'sort' => 'nd.nro_nota', 'tiebreaker' => 'nd.id_nota_debito', 'first' => 'desc'],
            'total' => ['label' => 'Total', 'sort' => 'nd.total', 'align' => 'right', 'tiebreaker' => 'nd.id_nota_debito', 'first' => 'desc'],
            'usuario' => ['label' => 'Usuario', 'sort' => 'users.name', 'tiebreaker' => 'nd.id_nota_debito'],
            'estado' => ['label' => 'Estado', 'sort' => 'nd.estado', 'tiebreaker' => 'nd.id_nota_debito'],
            'acciones' => ['label' => 'Action', 'width' => '140px'],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'fecha_emision', 'desc', $request);

        $notas = DB::select(
            "SELECT nd.*, concat(c.clie_nombre,' ', c.clie_apellido) as cliente, c.clie_ci,
            users.name as usuario, s.descripcion as sucursal
            FROM notas_debito nd
                JOIN clientes c ON nd.id_cliente = c.id_cliente
                JOIN users ON nd.user_id = users.id
                JOIN sucursales s ON nd.id_sucursal = s.id_sucursal
            {$sql_busqueda} {$sql_filtros} {$sqlSucursales}
            ORDER BY {$sort->orderBy()}"
        );

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $total = count($notas);
        $items = array_slice($notas, ($page - 1) * $perPage, $perPage);

        $notas = new LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $clientes = DB::table('clientes')
            ->selectRaw("id_cliente, concat(clie_nombre,' ', clie_apellido) as cliente")
            ->pluck('cliente', 'id_cliente');

        $sucursales = $this->getSucursalesPermitidas();

        $estados = [
            'COMPLETADO' => 'COMPLETADO',
            'ANULADO' => 'ANULADO'
        ];

        // Cajas para apertura (si la caja está cerrada)
        $cajas = DB::table('cajas')
            ->where('id_sucursal', auth()->user()->id_sucursal)
            ->pluck('descripcion', 'id_caja');

        // Verificar si caja está abierta
        $caja_abierta = DB::selectOne(
            "SELECT * FROM apertura_cierre_cajas
            WHERE user_id = ? AND estado = 'ABIERTA'",
            [auth()->user()->id]
        );

        return view('notas_debito.index', compact('notas', 'clientes', 'sucursales', 'estados', 'cajas', 'caja_abierta', 'columns', 'sort'));
    }

    public function create()
    {
        // Validar que el usuario tenga una caja abierta
        $caja_abierta = DB::selectOne(
            "SELECT * FROM apertura_cierre_cajas
            WHERE user_id = ? AND estado = 'ABIERTA'",
            [auth()->user()->id]
        );

        if (empty($caja_abierta)) {
            Alert::toast('Debe abrir una caja para crear notas de débito', 'error');
            return redirect()->route('notas_debito.index');
        }

        // Clientes activos
        $clientes = DB::table('clientes')
            ->where('activo', true)
            ->selectRaw("id_cliente, concat(clie_nombre,' ', clie_apellido) as cliente")
            ->pluck('cliente', 'id_cliente');

        // Sucursales permitidas
        $sucursales = $this->getSucursalesPermitidas();

        $usuario = auth()->user()->name;

        // Timbrados vigentes
        $timbrados_vigentes = [];
        $sucursal = DB::selectOne('SELECT id_empresa FROM sucursales WHERE id_sucursal = ?', [auth()->user()->id_sucursal]);
        if ($sucursal && $sucursal->id_empresa) {
            $timbrados_vigentes = Timbrado::where('id_empresa', $sucursal->id_empresa)
                ->notasDebito()
                ->vigentes()
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();
        }

        // Metodos de pago
        // Metodos de pago
        $metodo_pagos = $this->getMetodosPago();

        return view('notas_debito.create', compact('clientes', 'sucursales', 'usuario', 'timbrados_vigentes', 'metodo_pagos'));
    }

    public function store(Request $request)
    {
        $input = $request->all();
        
        $validator = Validator::make($input, [
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'fecha_emision' => 'required|date',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'id_timbrado' => 'required|exists:timbrados,id_timbrado',
            'codigo' => 'required|array', // Detalle required
            'codigo.*' => 'required',
            'id_metodo_pago' => 'nullable|exists:metodo_pagos,id_metodo_pago',
            'monto_cobrado' => 'nullable', // Should be validated if id_metodo_pago is present
        ]);

        if ($validator->fails()) {
             return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Recuperar caja abierta nuevamente para asegurar la transaccion
            $caja_abierta = DB::selectOne(
                "SELECT id_apertura FROM apertura_cierre_cajas
                WHERE user_id = ? AND estado = 'ABIERTA'",
                [auth()->user()->id]
            );

            if (empty($caja_abierta)) {
                throw new \Exception("Su caja ha sido cerrada. No se puede guardar la nota.");
            }

            $timbrado = Timbrado::find($input['id_timbrado']);
            
            // Generar número
            $numero_nota = $timbrado->generarNumeroFactura(); 

            if (!$numero_nota) {
                throw new \Exception("No se pudo generar el número de nota.");
            }

            $total = (float)str_replace('.', '', $input['total'] ?? 0);

            $id_nota = DB::table('notas_debito')->insertGetId([
                'id_cliente' => $input['id_cliente'],
                'id_sucursal' => $input['id_sucursal'],
                'id_timbrado' => $input['id_timbrado'],
                'id_venta' => $input['id_venta'], // Added id_venta
                'fecha_emision' => $input['fecha_emision'],
                'nro_nota' => $numero_nota,
                'total' => $total,
                'estado' => 'COMPLETADO',
                'user_id' => auth()->id(),
                'motivo' => $input['motivo'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_nota_debito');

            // Procesar detalles
            $subtotal = 0;
            $total_exento = 0;
            $total_gravado_5 = 0;
            $total_gravado_10 = 0;
            $total_iva_5 = 0;
            $total_iva_10 = 0;

            if ($request->has('codigo')) {
               $codigos = array_map('intval', $input['codigo']);
               $productosInfo = DB::table('productos as p')
                    ->leftJoin('taxes as t', 'p.id_tax', '=', 't.id_tax')
                    ->whereIn('p.id_producto', $codigos)
                    ->select('p.id_producto', 'p.id_tax', 'p.controlar_stock', 't.porcentaje')
                    ->get()
                    ->keyBy('id_producto');

               foreach ($input['codigo'] as $key => $codigo) {
                    $cantidad = $input['cantidad'][$key];
                    $precio_con_iva = (float)str_replace('.', '', $input['precio'][$key]);
                    
                    $prod = $productosInfo[$codigo] ?? null;
                    if (!$prod) continue;

                    $porcentaje = $prod->porcentaje ?? 0;
                    $id_tax = $prod->id_tax;

                    // Cálculos
                    $precio_sin_impuesto = $porcentaje > 0 
                        ? $precio_con_iva / (1 + ($porcentaje / 100)) 
                        : $precio_con_iva;
                    $monto_impuesto = $precio_con_iva - $precio_sin_impuesto;

                    $subtotal_item = $precio_con_iva * $cantidad;
                    $gravada_item = $precio_sin_impuesto * $cantidad;
                    $impuesto_total_item = $monto_impuesto * $cantidad;

                    // Acumuladores
                    $subtotal += $subtotal_item;
                     if ($porcentaje == 0) {
                        $total_exento += $gravada_item;
                    } elseif ($porcentaje == 5) {
                        $total_gravado_5 += $gravada_item;
                        $total_iva_5 += $impuesto_total_item;
                    } elseif ($porcentaje == 10) {
                        $total_gravado_10 += $gravada_item;
                        $total_iva_10 += $impuesto_total_item;
                    }

                    DB::table('detalle_notas_debito')->insert([
                        'id_nota_debito' => $id_nota,
                        'id_producto' => $codigo,
                        'cantidad' => $cantidad,
                        'precio' => $precio_con_iva,
                        'id_tax' => $id_tax,
                        'precio_sin_impuesto' => $gravada_item,
                        'monto_impuesto' => $monto_impuesto
                    ]);

                    // Descontar Stock
                    if ($prod->controlar_stock) {
                        DB::table('stocks')
                            ->where('id_producto', $codigo)
                            ->where('id_sucursal', $input['id_sucursal'])
                            ->decrement('cantidad', $cantidad);
                    }
               }
            }

            $total_iva = $total_iva_5 + $total_iva_10;

            // Update header totals
            DB::update(
                'UPDATE notas_debito SET total = ?, total_exento = ?, total_gravado_5 = ?, total_gravado_10 = ?, total_iva_5 = ?, total_iva_10 = ?, total_iva = ? WHERE id_nota_debito = ?',
                [$subtotal, $total_exento, $total_gravado_5, $total_gravado_10, $total_iva_5, $total_iva_10, $total_iva, $id_nota]
            );

            $timbrado->incrementarUso();

            // PROCESAR PAGOS (SPLIT PAYMENTS)
            $total_pagado = 0;
            if ($request->has('pagos') && is_array($request->pagos)) {
                foreach ($request->pagos as $pago) {
                    $monto_pago = (float)str_replace('.', '', $pago['monto']);
                    
                    if ($monto_pago > 0) {
                        DB::table('cobros')->insert([
                            'id_nota_debito' => $id_nota,
                            'user_id' => auth()->id(),
                            'id_metodo_pago' => $pago['id_metodo_pago'],
                            'cobro_fecha' => now(),
                            'cobro_importe' => $monto_pago,
                            'cobro_estado' => 'COBRADO',
                            'nro_voucher' => $pago['nro_voucher'] ?? null,
                            'id_cta' => null,
                            'id_apertura' => $caja_abierta->id_apertura
                        ]);
                        $total_pagado += $monto_pago;
                    }
                }
            }
            
            DB::commit();
            Alert::toast('Nota de Débito registrada exitosamente.', 'success');
            return redirect()->route('notas_debito.index')->with('print_nota_id', $id_nota);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $nota = NotaDebito::with(['cliente', 'usuario', 'detalles.producto'])->find($id);
        if (!$nota) {
            Alert::error('No encontrado');
            return redirect()->route('notas_debito.index');
        }

        // Obtener los cobros asociados a la nota de débito
        $pagos = DB::table('cobros as c')
            ->join('metodo_pagos as mp', 'c.id_metodo_pago', '=', 'mp.id_metodo_pago')
            ->where('c.id_nota_debito', $id)
            ->select('c.*', 'mp.descripcion as metodo_pago')
            ->get();

        return view('notas_debito.show', compact('nota', 'pagos'));
    }

    public function buscarProductosJson(Request $request) {
        $term = $request->term;
        $sucursal_id = $request->cod_suc;

        $words = explode(' ', $term);
        
        $productos = DB::table('productos')
            ->where(function($query) use ($words, $term) {
                // Option 1: Match description by matching ALL words (in any order)
                $query->where(function($subQuery) use ($words) {
                    foreach ($words as $word) {
                        if (!empty(trim($word))) {
                             $subQuery->where('descripcion', 'ILIKE', '%' . trim($word) . '%');
                        }
                    }
                });

                // Match Code Bar or ID
                $query->orWhere('codigo_barras', 'ILIKE', '%' . $term . '%');
                $query->orWhereRaw("CAST(id_producto AS TEXT) ILIKE ?", ['%' . $term . '%']);
            })
            ->limit(20)
            ->get();
        
        $results = [];
        foreach($productos as $prod) {
            // Basic pricing logic
            $precio = $prod->precio ?? 0;
            // Fetch tax
            $tax = 10;
            if($prod->id_tax) {
                 $taxRow = DB::table('taxes')->where('id_tax', $prod->id_tax)->first();
                 $tax = $taxRow->porcentaje ?? 10;
            }

            // Stock Logic
            $stockMsg = '';
            $cantidad = 0;
            if ($prod->controlar_stock) {
                $queryStock = DB::table('stocks')->where('id_producto', $prod->id_producto);
                if ($sucursal_id && is_numeric($sucursal_id)) {
                    $queryStock->where('id_sucursal', $sucursal_id);
                }
                $cantidad = $queryStock->sum('cantidad');
                $stockMsg = " | Stock: " . number_format($cantidad, 0, ',', '.');
            }

            $results[] = [
                'id' => $prod->id_producto,
                'cod_barra' => $prod->codigo_barras ?? $prod->id_producto,
                'descripcion' => $prod->descripcion,
                'precio' => $precio,
                'iva' => $tax,
                'stock_msg' => $stockMsg,
                'stock_cantidad' => (float)$cantidad,
                'controlar_stock' => (bool)$prod->controlar_stock
            ];
        }

        return response()->json(['results' => $results]);
    }

    public function buscarVentasJson(Request $request) {
        $term = $request->term ?: '';
        $ventas = Venta::where('factura_nro', 'ILIKE', "%$term%")
            ->orWhereHas('cliente', function($q) use ($term) {
                $q->where('clie_nombre', 'ILIKE', "%$term%")
                  ->orWhere('clie_apellido', 'ILIKE', "%$term%")
                  ->orWhere('clie_ci', 'ILIKE', "%$term%");
            })
            ->limit(20)
            ->get();
        
        $results = [];
        foreach($ventas as $venta) {
            $results[] = [
                'id' => $venta->id_venta,
                'factura' => $venta->factura_nro,
                'cliente' => ($venta->cliente->clie_nombre ?? '') . ' ' . ($venta->cliente->clie_apellido ?? ''),
                'fecha' => Carbon::parse($venta->fecha_venta)->format('d/m/Y'),
                'total' => number_format($venta->total, 0, ',', '.'),
                'id_cliente' => $venta->id_cliente,
                'id_sucursal' => $venta->id_sucursal
            ];
        }

        return response()->json(['results' => $results]);
    }

    public function edit($id)
    {
        // Validar que el usuario tenga una caja abierta
        $caja_abierta = DB::selectOne(
            "SELECT * FROM apertura_cierre_cajas
            WHERE user_id = ? AND estado = 'ABIERTA'",
            [auth()->user()->id]
        );

        if (empty($caja_abierta)) {
            Alert::toast('Debe abrir una caja para editar notas de débito', 'error');
            return redirect()->route('notas_debito.index');
        }

        $nota = NotaDebito::with(['detalles.producto', 'venta.cliente', 'cobros'])->find($id);

        if (empty($nota)) {
            Alert::error('Error', 'Nota de Débito no encontrada.');
            return redirect()->route('notas_debito.index');
        }

        // VALIDACIÓN ESTRICTA: Solo permitir editar notas creadas hoy
        // (Al quitar id_apertura, usaremos fecha como proxy o chequearemos cobros si es necesario)
        if (substr($nota->fecha_emision, 0, 10) != substr(now(), 0, 10)) {
            Alert::error('Acceso Denegado', 'Solo puede editar Notas de Débito creadas hoy.');
            return redirect()->route('notas_debito.index');
        }

        if (empty($nota)) {
            Alert::error('Error', 'Nota de Débito no encontrada.');
            return redirect()->route('notas_debito.index');
        }

        if ($nota->estado == 'ANULADO') {
            Alert::warning('Atención', 'No se puede editar una nota anulada.');
            return redirect()->route('notas_debito.index');
        }

        // Sucursales permitidas
        $sucursales = $this->getSucursalesPermitidas(auth()->user(), $nota->id_sucursal);

        $usuario = auth()->user()->name;

        // Timbrados vigentes (incluyendo el actual de la nota)
        $timbrados_vigentes = [];
        $sucursal = DB::selectOne('SELECT id_empresa FROM sucursales WHERE id_sucursal = ?', [$nota->id_sucursal]);
        if ($sucursal && $sucursal->id_empresa) {
            $timbrados_vigentes = Timbrado::where('id_empresa', $sucursal->id_empresa)
                ->where(function($query) use ($nota) {
                    $query->where(function($q) {
                         $q->where('fecha_vencimiento', '>=', now())
                           ->whereColumn('rango_fin', '>', 'numero_actual')
                           ->where('estado', 'VIGENTE');
                    })
                    ->orWhere('id_timbrado', $nota->id_timbrado);
                })
                ->notasDebito()
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();
        }

        // Metodos de pago
        // Metodos de pago
        $cobro = DB::table('cobros')->where('id_nota_debito', $id)->first();
        $idMetodoPagoActual = $cobro ? $cobro->id_metodo_pago : null;
        $metodo_pagos = $this->getMetodosPago($idMetodoPagoActual);

        return view('notas_debito.edit', compact('nota', 'sucursales', 'usuario', 'timbrados_vigentes', 'metodo_pagos'));
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        
        // Basic validation
        Validator::make($input, [
            'fecha_emision' => 'required|date',
            'id_timbrado' => 'required',
            'motivo' => 'required',
            'detalle_data' => 'required', // JSON string
        ])->validate();

        $nota = NotaDebito::find($id);

        if (empty($nota)) {
            Alert::error('Error', 'Nota de Débito no encontrada.');
            return redirect()->route('notas_debito.index');
        }

        if ($nota->estado == 'ANULADO') {
            Alert::warning('Error', 'No se puede editar una nota anulada.');
            return redirect()->route('notas_debito.index');
        }

        // Parse detail data
        $detalles = json_decode($input['detalle_data'], true);
        if (empty($detalles)) {
            Alert::error('Error', 'Debe indicar al menos un ítem.');
            return redirect()->back();
        }

        // 1. Validate Total Constraint
        $nuevoTotal = 0;
        foreach ($detalles as $d) {
            $nuevoTotal += $d['subtotal'];
        }

        // Compare integers to avoid float precision weirdness
        if ((int)$nuevoTotal !== (int)$nota->total) {
             Alert::error('Error de Validación', 'El monto total (' . number_format($nuevoTotal, 0, ',', '.') . ') no coincide con el total original (' . number_format($nota->total, 0, ',', '.') . '). No se permite modificar el total fiscal.');
             return redirect()->back()->withInput();
        }


        DB::beginTransaction();
        try {
            // 2. Restore Stock of OLD items
            // We need to fetch the old details before we delete them or update them? 
            // Better to fetch them, restore stock, then delete them (or diff them, but full replace is safer logic-wise here)
            $oldDetalles = DB::select('SELECT * FROM detalle_notas_debito WHERE id_nota_debito = ?', [$id]);
            foreach ($oldDetalles as $oldD) {
                // Return to stock (add back what was deducted)
                // Note: Logic depends on what Debit Note does. 
                // Creating a Debit Note implies 'selling' or 'removing' stock (like a delayed invoice)? 
                // Or is it a financial charge?
                // The destroy method ADDED to stock: "UPDATE stocks SET cantidad = cantidad + ?"
                // So creating/editing implies REMOVING from stock.
                // So to "undo" the old version, we ADD back to stock.
                
                // Only if product controls stock
                $prod = DB::table('productos')->where('id_producto', $oldD->id_producto)->first();
                if ($prod && $prod->controlar_stock) {
                     DB::update('UPDATE stocks SET cantidad = cantidad + ? WHERE id_producto = ? AND id_sucursal = ?', [
                        $oldD->cantidad,
                        $oldD->id_producto,
                        $nota->id_sucursal
                    ]);
                }
            }

            // 3. Delete OLD details
            DB::delete('DELETE FROM detalle_notas_debito WHERE id_nota_debito = ?', [$id]);


            // 4. Update Header Info
            // Start process of inserting NEW details and deducting stock
            // We do NOT update 'total', 'id_cliente', 'id_sucursal' (usually fixed)
            $nota->fecha_emision = $input['fecha_emision'];
            $nota->id_timbrado = $input['id_timbrado']; // Timbrado might change if date changes drastically?
            $nota->motivo = $input['motivo'];
            $nota->user_id = auth()->user()->id; // Update editor?
            $nota->save();

            // 5. Insert NEW details and Deduct Stock
            foreach ($detalles as $detalle) {
                $detalleNota = new DetalleNotaDebito();
                $detalleNota->id_nota_debito = $nota->id_nota_debito;
                $detalleNota->id_producto = $detalle['id_producto'];
                $detalleNota->cantidad = $detalle['cantidad'];
                $detalleNota->precio_unitario = $detalle['precio'];
                $detalleNota->precio_total = $detalle['subtotal'];
                $detalleNota->tax_porcentaje = isset($detalle['tax_porcentaje']) ? $detalle['tax_porcentaje'] : 10;
                
                // Calculate tax amount
                $tax_amount = 0;
                if ($detalleNota->tax_porcentaje > 0) {
                    $tax_amount = $detalleNota->precio_total / (1 + ($detalleNota->tax_porcentaje / 100)) * ($detalleNota->tax_porcentaje / 100);
                }
                $detalleNota->iva = $tax_amount;
                
                $detalleNota->save();

                // Deduct from stock
                $prod = DB::table('productos')->where('id_producto', $detalle['id_producto'])->first();
                if ($prod && $prod->controlar_stock) {
                    DB::update('UPDATE stocks SET cantidad = cantidad - ? WHERE id_producto = ? AND id_sucursal = ?', [
                        $detalle['cantidad'],
                        $detalle['id_producto'],
                        $nota->id_sucursal
                    ]);
                }
            }

            // 6. Update Payments
            // Delete old payments
            DB::delete('DELETE FROM cobros WHERE id_nota_debito = ?', [$id]);

            // Insert new payments
            if (isset($input['pagos']) && is_array($input['pagos'])) {
                foreach ($input['pagos'] as $pago) {
                    $montoPago = (int)str_replace('.', '', $pago['monto']);
                    if ($montoPago > 0) {
                        DB::table('cobros')->insert([
                            'id_nota_debito' => $nota->id_nota_debito,
                            'id_metodo_pago' => $pago['id_metodo_pago'],
                            'cobro_importe' => $montoPago,
                            'nro_voucher' => $pago['nro_voucher'] ?? null,
                            'cobro_fecha' => Carbon::now(),
                            'cobro_estado' => 'COMPLETADO'
                        ]);
                    }
                }
            }

            DB::commit();
            
            Alert::success('Éxito', 'Nota de Débito actualizada correctamente.');
            return redirect()->route('notas_debito.index');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            Alert::error('Error', 'Hubo un error al actualizar la nota: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($id)
    {
        $nota = DB::selectOne('SELECT * FROM notas_debito WHERE id_nota_debito = ?', [$id]);

        if (empty($nota)) {
            Alert::error('Error', 'Nota de Débito no encontrada.');
            return redirect()->route('notas_debito.index');
        }

        if ($nota->estado == 'ANULADO') {
            Alert::warning('Atención', 'Esta nota ya se encuentra anulada.');
            return redirect()->route('notas_debito.index');
        }

        DB::beginTransaction();
        try {
            // 1. Anular la Nota de Débito
            DB::update('UPDATE notas_debito SET estado = ? WHERE id_nota_debito = ?', ['ANULADO', $id]);

            // 2. Restaurar Stock
            $detalles = DB::select('SELECT * FROM detalle_notas_debito WHERE id_nota_debito = ?', [$id]);
            
            $productosIds = array_unique(array_map(fn($item) => (int) $item->id_producto, $detalles));
            $productosControl = empty($productosIds)
                ? []
                : DB::table('productos')
                    ->whereIn('id_producto', $productosIds)
                    ->pluck('controlar_stock', 'id_producto')
                    ->mapWithKeys(fn($value, $key) => [(int) $key => (bool) $value])
                    ->toArray();

            foreach ($detalles as $detalle) {
                $controlarStock = $productosControl[(int) $detalle->id_producto] ?? true;
                if ($controlarStock) {
                    DB::update('UPDATE stocks SET cantidad = cantidad + ? WHERE id_producto = ? AND id_sucursal = ?', [
                        $detalle->cantidad,
                        $detalle->id_producto,
                        $nota->id_sucursal
                    ]);
                }
            }

            // 3. Anular Cobros relacionados
            DB::update('UPDATE cobros SET cobro_estado = ? WHERE id_nota_debito = ?', ['ANULADO', $id]);

            DB::commit();
            Alert::success('Éxito', 'Nota de Débito anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al anular Nota de Débito: ' . $e->getMessage());
            Alert::error('Error', 'No se pudo anular la nota: ' . $e->getMessage());
        }

        return redirect()->route('notas_debito.index');
    }

    public function pdf($id)
    {
        $nota = NotaDebito::with(['cliente', 'usuario', 'sucursal.empresa', 'timbrado', 'detalles.producto.tax', 'detalles.tax', 'venta'])->find($id);

        if (empty($nota)) {
            Flash::error('Nota de Débito no encontrada');
            return redirect(route('notas_debito.index'));
        }

        $formatter = new NumeroALetras();
        $numero_a_letras = $formatter->toWords($nota->total);

        return view('notas_debito.pdf', compact('nota', 'numero_a_letras'));
    }
}
