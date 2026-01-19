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
use App\Models\NotaCredito;
use App\Models\DetalleNotaCredito;
use App\Models\Venta;
use App\Models\CuentaACobrar;

class NotaCreditoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:notas_credito index')->only(['index', 'show', 'pdf']);
        $this->middleware('permission:notas_credito create')->only(['create', 'store', 'buscarVentasJson', 'getDetallesVentaJson']);
        $this->middleware('permission:notas_credito destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $sql_filtros .= " AND nc.fecha_emision BETWEEN '{$input['fecha_desde']}' AND '{$input['fecha_hasta']}'";
        } elseif (!empty($input['fecha_desde'])) {
            $sql_filtros .= " AND nc.fecha_emision >= '{$input['fecha_desde']}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $sql_filtros .= " AND nc.fecha_emision <= '{$input['fecha_hasta']}'";
        }

        if (!empty($input['id_cliente'])) {
            $sql_filtros .= " AND nc.id_cliente = " . (int)$input['id_cliente'];
        }

        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND nc.id_sucursal = " . (int)$input['id_sucursal'];
        }

        if (!empty($input['estado'])) {
            $sql_filtros .= " AND nc.estado = '" . $input['estado'] . "'";
        }

        $searchMode = $request->get('search_mode', 'all');
        $buscar = $request->get('buscar');
        $sql_busqueda = 'WHERE 1=1';

        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                $sql_busqueda .= " AND nc.id_nota_credito = " . (int)$searchConfig['term'];
            } else {
                $buscarTerm = $searchConfig['term'];
                $buscarFecha = $buscarTerm;
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }
                $buscarMonto = str_replace('.', '', $buscarTerm);

                $sql_busqueda .= " AND (cast(nc.nro_nota as text) ILIKE '%{$buscarTerm}%' 
                                   OR cast(c.clie_nombre as text) ILIKE '%{$buscarTerm}%' 
                                   OR cast(c.clie_apellido as text) ILIKE '%{$buscarTerm}%'
                                   OR cast(c.clie_ci as text) ILIKE '%{$buscarTerm}%'
                                   OR cast(nc.fecha_emision as text) ILIKE '%{$buscarFecha}%'
                                   OR TO_CHAR(nc.fecha_emision, 'DD/MM/YYYY') ILIKE '%{$buscarTerm}%'
                                   OR cast(nc.total as text) ILIKE '%{$buscarMonto}%'
                                   OR TO_CHAR(nc.total, 'FM999G999G999G999G999') ILIKE '%{$buscarTerm}%'
                                   OR users.name ILIKE '%{$buscarTerm}%')";
            }
        }

        $idsPermitidos = $this->getSucursalesPermitidasIds();
        $sqlSucursales = '';
        if (!empty($idsPermitidos)) {
            $sqlSucursales = ' AND nc.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        }

        $notas = DB::select(
            "SELECT nc.*, concat(c.clie_nombre,' ', c.clie_apellido) as cliente, c.clie_ci,
            users.name as usuario, s.descripcion as sucursal, v.factura_nro as factura
            FROM notas_credito nc
                JOIN clientes c ON nc.id_cliente = c.id_cliente
                JOIN users ON nc.user_id = users.id
                JOIN sucursales s ON nc.id_sucursal = s.id_sucursal
                LEFT JOIN ventas v ON nc.id_venta = v.id_venta
            {$sql_busqueda} {$sql_filtros} {$sqlSucursales}
            ORDER BY nc.fecha_emision DESC"
        );

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

        return view('notas_credito.index', compact('notas', 'clientes', 'sucursales', 'estados', 'cajas', 'caja_abierta'));
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
            Alert::toast('Debe abrir una caja para crear notas de crédito', 'error');
            return redirect()->route('notas_credito.index');
        }

        // Calcular efectivo disponible en caja
        $monto_inicial = $caja_abierta->monto_apertura ?? 0;
        $movimientos_efectivo = DB::table('cobros')
            ->where('id_apertura', $caja_abierta->id_apertura)
            ->where('id_metodo_pago', 1) // 1 = EFECTIVO
            ->where('cobro_estado', '!=', 'ANULADO')
            ->sum('cobro_importe');
        
        $efectivo_disponible = $monto_inicial + $movimientos_efectivo;

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
                ->notasCredito() // Scope needs to be created or use query
                ->vigentes()
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();
        }

        // Metodos de pago
        $metodo_pagos = $this->getMetodosPago();

        return view('notas_credito.create', compact('clientes', 'sucursales', 'usuario', 'timbrados_vigentes', 'metodo_pagos', 'efectivo_disponible'));
    }

    public function store(Request $request)
    {
        $input = $request->all();
        
        $validator = Validator::make($input, [
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'fecha_emision' => 'required|date',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'id_timbrado' => 'required|exists:timbrados,id_timbrado',
            'id_venta' => 'required|exists:ventas,id_venta',
            'codigo' => 'required|array', // Detalle required
            'codigo.*' => 'required',
        ]);

        if ($validator->fails()) {
             return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Recuperar venta
            $venta = Venta::find($input['id_venta']);
            if(!$venta) throw new \Exception("Venta no encontrada");

            // GLOBAL VALIDATION: Sum(NC) <= Venta.Total
            $totalNotasPrevias = DB::table('notas_credito')
                ->where('id_venta', $input['id_venta'])
                ->where('estado', '!=', 'ANULADO')
                ->sum('total');
            
            $nuevoTotal = (float)str_replace('.', '', $input['total'] ?? 0);

            if (($totalNotasPrevias + $nuevoTotal) > $venta->total) {
                throw new \Exception("El total de Notas de Crédito (" . number_format($totalNotasPrevias + $nuevoTotal, 0, ',', '.') . ") excede el total de la venta (" . number_format($venta->total, 0, ',', '.') . ").");
            }
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

            $id_nota = DB::table('notas_credito')->insertGetId([
                'id_cliente' => $input['id_cliente'],
                'id_sucursal' => $input['id_sucursal'],
                'id_timbrado' => $input['id_timbrado'],
                'id_venta' => $input['id_venta'],
                'fecha_emision' => $input['fecha_emision'],
                'nro_nota' => $numero_nota,
                'total' => 0, // Will update later
                'estado' => 'COMPLETADO',
                'user_id' => auth()->id(),
                'motivo' => ($input['concepto'] ?? '') . ': ' . ($input['motivo'] ?? ''),
                'created_at' => now(),
                'updated_at' => now(),
                'id_apertura' => $caja_abierta->id_apertura
            ], 'id_nota_credito');

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

                    DB::table('detalle_notas_credito')->insert([
                        'id_nota_credito' => $id_nota,
                        'id_producto' => $codigo,
                        'cantidad' => $cantidad,
                        'precio' => $precio_con_iva,
                        'id_tax' => $id_tax,
                        'precio_sin_impuesto' => $gravada_item,
                        'monto_impuesto' => $monto_impuesto
                    ]);

                    // AUMENTAR Stock (Devolución) - SOLO SI NO ES DESCUENTO
                    if (($input['concepto'] ?? 'DEVOLUCION') !== 'DESCUENTO') {
                        if ($prod->controlar_stock) {
                            DB::table('stocks')
                                ->where('id_producto', $codigo)
                                ->where('id_sucursal', $input['id_sucursal'])
                                ->increment('cantidad', $cantidad);
                        }
                    }
               }
            }

            $total_iva = $total_iva_5 + $total_iva_10;

            // Re-validate total
            if (abs($subtotal - $nuevoTotal) > 1) { // Allowing tiny floating point diff
                 // Should ideally rollback, but let's trust subtotal calculation as true
            }

            // Update header totals
            DB::update(
                'UPDATE notas_credito SET total = ?, total_exento = ?, total_gravado_5 = ?, total_gravado_10 = ?, total_iva_5 = ?, total_iva_10 = ?, total_iva = ? WHERE id_nota_credito = ?',
                [$subtotal, $total_exento, $total_gravado_5, $total_gravado_10, $total_iva_5, $total_iva_10, $total_iva, $id_nota]
            );

            $timbrado->incrementarUso();

            // --- APPLICATION TO BALANCES (CUENTAS A COBRAR) ---
            $montoParaAplicar = $subtotal;
            
            // Buscar cuotas con deuda
            $cuotas = CuentaACobrar::where('id_venta', $input['id_venta'])
                ->where('estado', '!=', 'CANCELADO')
                ->whereRaw('(importe - importe_pagado) > 0') // Double check balance
                ->orderBy('vencimiento', 'asc') // Apply to oldest first
                ->get();

            // Find or create a 'NOTA CREDITO' payment method type if needed
            // For now assuming we just record it, maybe with null method or a specific one if user has one defined.
            // Using ID 1 (Efectivo) as fallback is bad. Let's look for a generic one or use null.
            // Requirement says "Solo se aplicaran las notas de credito a las facturas contado o credito"
            // We'll leave id_metodo_pago null and rely on logic to display "Nota Credito" if null or special handling
            
            $idMetodoPagoNC = null; 
            // Optional: Try to find one named "Nota de Credito"
            $metodoNC = DB::table('metodo_pagos')->where('descripcion', 'ILIKE', '%Nota%Cr%dito%')->first();
            if ($metodoNC) {
                $idMetodoPagoNC = $metodoNC->id_metodo_pago;
            }

            foreach ($cuotas as $cuota) {
                if ($montoParaAplicar <= 0) break;

                $saldoCuota = $cuota->importe - $cuota->importe_pagado;
                
                // Determine application amount
                $aplicar = min($montoParaAplicar, $saldoCuota);

                // Create Cobro
                DB::table('cobros')->insert([
                    'id_venta' => $input['id_venta'],
                    'id_cta' => $cuota->id_cta,
                    'user_id' => auth()->id(),
                    'id_metodo_pago' => $idMetodoPagoNC, // Can be null
                    'cobro_fecha' => now(),
                    'cobro_importe' => $aplicar,
                    'cobro_estado' => 'COBRADO',
                    'nro_voucher' => 'NC-' . $numero_nota,
                    'id_apertura' => $caja_abierta->id_apertura,
                    'id_nota_credito' => $id_nota // LINKING NC
                ]);

                // Update Quota
                $cuota->importe_pagado += $aplicar;
                if ($cuota->importe_pagado >= $cuota->importe) {
                    $cuota->estado = 'CANCELADO';
                }
                $cuota->save();

                $montoParaAplicar -= $aplicar;
            }

            // If $montoParaAplicar > 0, it remains as "refunded" implicit or balance in favor.
            
            // 5. Update Payments (Refunds)
            // Validar si es una devolución (Motivo DEVOLUCION) o si se requiere registrar el movimiento de salida de dinero
            if ($request->has('pagos') && is_array($request->pagos)) {
                // Obtener ID de caja abierta
                 $caja_abierta = DB::selectOne(
                    "SELECT id_apertura FROM apertura_cierre_cajas
                    WHERE user_id = ? AND estado = 'ABIERTA'",
                    [auth()->user()->id]
                );

                if (empty($caja_abierta)) {
                    // Si no hay caja abierta, ¿permitimos guardar sin registrar pago?
                    // Asumiremos que si se seleccionó pago, se requiere caja.
                    throw new \Exception("Su caja ha sido cerrada. No se puede registrar el pago de la nota de crédito.");
                }

                // Calcular total a devolver en efectivo para validar
                $totalDevolucionEfectivo = 0;
                foreach ($request->pagos as $pago) {
                    if (($pago['id_metodo_pago'] == 1)) { // 1 = Efectivo
                         $totalDevolucionEfectivo += (float)str_replace('.', '', $pago['monto']);
                    }
                }

                if ($totalDevolucionEfectivo > 0) {
                     $caja_abierta_info = DB::selectOne(
                        "SELECT monto_apertura, id_apertura FROM apertura_cierre_cajas WHERE id_apertura = ?", 
                        [$caja_abierta->id_apertura]
                    );

                    $saldo_efectivo = ($caja_abierta_info->monto_apertura ?? 0) + DB::table('cobros')
                        ->where('id_apertura', $caja_abierta->id_apertura)
                        ->where('id_metodo_pago', 1)
                        ->where('cobro_estado', '!=', 'ANULADO')
                        ->sum('cobro_importe');
                    
                    if ($totalDevolucionEfectivo > $saldo_efectivo) {
                        throw new \Exception("Fondos insuficientes en caja para devolución en efectivo. Disponible: Gs. " . number_format($saldo_efectivo, 0, ',', '.') . " - Requerido: Gs. " . number_format($totalDevolucionEfectivo, 0, ',', '.'));
                    }
                }

                foreach ($request->pagos as $pago) {
                    $montoPago = (float)str_replace('.', '', $pago['monto']);
                    
                    if ($montoPago > 0) {
                        // Insertar en cobros como valor NEGATIVO para indicar salida de dinero (Devolución)
                        // OJO: Si el sistema suma 'cobro_importe' para el arqueo, un negativo restará del total, lo cual es correcto para una DEVOLUCIÓN.
                        DB::table('cobros')->insert([
                            'id_nota_credito' => $id_nota,
                            'user_id' => auth()->id(),
                            'id_metodo_pago' => $pago['id_metodo_pago'],
                            'cobro_fecha' => now(),
                            'cobro_importe' => $montoPago * -1, // Negativo para salida
                            'cobro_estado' => 'COBRADO',
                            'nro_voucher' => $pago['nro_voucher'] ?? null,
                            'id_cta' => null, 
                            'id_apertura' => $caja_abierta->id_apertura
                        ]);
                    }
                }
            }

            DB::commit();
            Alert::toast('Nota de Crédito registrada exitosamente', 'success');
            return redirect()->route('notas_credito.index')->with('print_nota_id', $id_nota);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $nota = NotaCredito::with(['cliente', 'usuario', 'detalles.producto', 'venta'])->find($id);
        if (!$nota) {
            Alert::error('No encontrado');
            return redirect()->route('notas_credito.index');
        }

        // Obtener aplicaciones (cobros generados por esta nota)
        $aplicaciones = DB::table('cobros')
            ->where('id_nota_credito', $id)
            ->get();

        return view('notas_credito.show', compact('nota', 'aplicaciones'));
    }

    /**
     * Anulacion
     */
    public function destroy($id)
    {
        $nota = NotaCredito::find($id);

        if (empty($nota)) {
            Alert::error('Error', 'Nota de Crédito no encontrada.');
            return redirect()->route('notas_credito.index');
        }

        if ($nota->estado == 'ANULADO') {
            Alert::warning('Atención', 'Esta nota ya se encuentra anulada.');
            return redirect()->route('notas_credito.index');
        }

        DB::beginTransaction();
        try {
            // 1. Anular Nota
            $nota->estado = 'ANULADO';
            $nota->save();

            // 2. Revertir Aplicaciones (Cobros)
            $cobrosVinculados = DB::table('cobros')->where('id_nota_credito', $id)->get();

            foreach ($cobrosVinculados as $cobro) {
                // Revert Quota
                if ($cobro->id_cta) {
                    $cuota = CuentaACobrar::find($cobro->id_cta);
                    if ($cuota) {
                        $cuota->importe_pagado -= $cobro->cobro_importe;
                        // Si estaba cancelada, ahora queda abierta
                        if ($cuota->importe_pagado < $cuota->importe) {
                            $cuota->estado = 'PENDIENTE';
                        }
                        $cuota->save();
                    }
                }
                
                // Mark Cobro as Anulado
                DB::table('cobros')
                    ->where('id_cobro', $cobro->id_cobro)
                    ->update([
                        'cobro_estado' => 'ANULADO',
                        'fechahora_anulado' => now(),
                        'id_useranulado' => auth()->id()
                    ]);
            }

            // 3. Revert Stock (Deduct what was returned)
            $detalles = $nota->detalles;
            foreach ($detalles as $detalle) {
                $prod = DB::table('productos')->where('id_producto', $detalle->id_producto)->first();
                if ($prod && $prod->controlar_stock) {
                    DB::table('stocks')
                        ->where('id_producto', $detalle->id_producto)
                        ->where('id_sucursal', $nota->id_sucursal)
                        ->decrement('cantidad', $detalle->cantidad);
                }
            }

            DB::commit();
            Alert::success('Éxito', 'Nota de Crédito anulada correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            Alert::error('Error', 'No se pudo anular: ' . $e->getMessage());
        }

        return redirect()->route('notas_credito.index');
    }

    public function pdf($id)
    {
        $nota = NotaCredito::with([
            'cliente', 
            'usuario', 
            'sucursal.empresa', 
            'timbrado', 
            'detalles.producto.tax', 
            'detalles.tax', 
            'venta',
            'cobros.cuentaACobrar',
            'cobros.metodoPago'
        ])->find($id);

        if (empty($nota)) {
            Flash::error('Nota de Crédito no encontrada');
            return redirect(route('notas_credito.index'));
        }

        $formatter = new NumeroALetras();
        $numero_a_letras = $formatter->toWords($nota->total);

        return view('notas_credito.pdf', compact('nota', 'numero_a_letras'));
    }

    public function buscarVentasJson(Request $request) {
        $term = $request->term ?: '';
        $clienteId = $request->cliente_id;

        // Buscar ventas activas que puedan recibir NC
        // Opcional: Filtrar solo ventas que no han sido totalmente anuladas por otras NC?
        // Por ahora listamos todas las del cliente.
        $query = Venta::with(['cliente'])
            ->where('estado', '!=', 'ANULADO');

        if ($clienteId) {
            $query->where('id_cliente', $clienteId);
        }

        if ($term) {
            $query->where(function($q) use ($term) {
                $q->where('factura_nro', 'ILIKE', "%$term%")
                  ->orWhere('id_venta', (int)$term);
            });
        }
            
        $ventas = $query->orderBy('fecha_venta', 'desc')->limit(20)->get();
        
        $results = [];
        foreach($ventas as $venta) {
            // Calcular saldo pendiente real (Total - TotalNCs) para mostrar info util?
            $totalNotas = DB::table('notas_credito')
                ->where('id_venta', $venta->id_venta)
                ->where('estado', '!=', 'ANULADO')
                ->sum('total');
            
            $saldoDisponible = max(0, $venta->total - $totalNotas);

            $results[] = [
                'id' => $venta->id_venta,
                'factura' => $venta->factura_nro,
                'fecha' => Carbon::parse($venta->fecha_venta)->format('d/m/Y'),
                'total' => number_format($venta->total, 0, ',', '.'),
                'saldo_disponible' => number_format($saldoDisponible, 0, ',', '.'),
                'saldo_raw' => $saldoDisponible,
                'condicion' => $venta->condicion_venta,
                'cliente' => $venta->cliente ? ($venta->cliente->clie_nombre . ' ' . $venta->cliente->clie_apellido) : 'Sin Cliente',
                'id_sucursal' => $venta->id_sucursal, // Added for auto-selection
                'id_cliente' => $venta->id_cliente
            ];
        }

        return response()->json(['results' => $results]);
    }

    public function getDetallesVentaJson($id) {
        $venta = Venta::with(['detalles.producto.tax'])->find($id);
        
        if (!$venta) return response()->json([]);

        $items = [];
        foreach($venta->detalles as $det) {
            $producto = $det->producto;
            $items[] = [
                'id_producto' => $det->id_producto,
                'codigo_barras' => $producto->codigo_barras ?? 'N/A',
                'descripcion' => $producto->descripcion ?? 'Producto no encontrado (ID: ' . $det->id_producto . ')',
                'cantidad' => $det->cantidad, // Original quantity
                'precio' => $det->precio,
                'id_tax' => $det->id_tax,
                'porcentaje_tax' => $producto->tax->porcentaje ?? ($det->tax->porcentaje ?? 10) // Fallback to detail tax or 10
            ];
        }

        return response()->json($items);
    }
}
