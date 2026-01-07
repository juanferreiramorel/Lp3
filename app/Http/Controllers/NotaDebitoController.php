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
use App\Models\NotaDebito;
use App\Models\DetalleNotaDebito;
use App\Models\Venta;

class NotaDebitoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Asignar permisos si existen, o usar genéricos por ahora
        // $this->middleware('permission:ventas index')->only(['index', 'show']);
        // $this->middleware('permission:ventas create')->only(['create', 'store']);
    }

    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // Filtros (similar a Ventas)
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $sql_filtros .= " AND nd.fecha_emision BETWEEN '{$input['fecha_desde']}' AND '{$input['fecha_hasta']}'";
        }

        if (!empty($input['id_cliente'])) {
            $sql_filtros .= " AND nd.id_cliente = " . (int)$input['id_cliente'];
        }

        $searchMode = $request->get('search_mode', 'all');
        $buscar = $request->get('buscar');
        $sql_busqueda = 'WHERE 1=1';

        if (!empty($buscar)) {
             // Búsqueda simple por nro o cliente
             $sql_busqueda .= " AND (cast(nd.nro_nota as text) ILIKE '%{$buscar}%' 
                                OR cast(c.clie_nombre as text) ILIKE '%{$buscar}%' 
                                OR cast(c.clie_apellido as text) ILIKE '%{$buscar}%'
                                OR cast(c.clie_ci as text) ILIKE '%{$buscar}%')";
        }

        $notas = DB::select(
            "SELECT nd.*, concat(c.clie_nombre,' ', c.clie_apellido) as cliente, c.clie_ci,
            users.name as usuario
            FROM notas_debito nd
                JOIN clientes c ON nd.id_cliente = c.id_cliente
                JOIN users ON nd.user_id = users.id
            {$sql_busqueda} {$sql_filtros}
            ORDER BY nd.fecha_emision DESC"
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

        return view('notas_debito.index', compact('notas', 'clientes'));
    }

    public function create()
    {
        // Clientes activos
        $clientes = DB::table('clientes')
            ->where('activo', true)
            ->selectRaw("id_cliente, concat(clie_nombre,' ', clie_apellido) as cliente")
            ->pluck('cliente', 'id_cliente');

        // Sucursales permitidas
        $sucursales = DB::table('sucursales')
            ->where('id_sucursal', auth()->user()->id_sucursal)
            ->pluck('descripcion', 'id_sucursal');

        $usuario = auth()->user()->name;

        // Timbrados vigentes
        $timbrados_vigentes = [];
        $sucursal = DB::selectOne('SELECT id_empresa FROM sucursales WHERE id_sucursal = ?', [auth()->user()->id_sucursal]);
        if ($sucursal && $sucursal->id_empresa) {
            $timbrados_vigentes = Timbrado::where('id_empresa', $sucursal->id_empresa)
                ->vigentes()
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();
        }

        // Metodos de pago
        $metodo_pagos = DB::table('metodo_pagos')->pluck('descripcion', 'id_metodo_pago');

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
                            'id_cta' => null 
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
        return view('notas_debito.show', compact('nota'));
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
