<?php

namespace App\Http\Controllers;

use App\Models\Timbrado;
use App\Models\Empresa;
use App\Models\TipoTimbrado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;

class TimbradoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->middleware('permission:timbrados index');

        $this->middleware('permission:timbrados index');

        $query = Timbrado::with(['empresa', 'tipotimbrado']);

        // Filtro por empresa
        if ($request->filled('id_empresa')) {
            $query->where('id_empresa', $request->id_empresa);
        }

        // Filtro por tipo de timbrado
        if ($request->filled('id_tipo_timbrado')) {
            $query->where('id_tipo_timbrado', $request->id_tipo_timbrado);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por búsqueda
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('numero_timbrado', 'LIKE', "%{$buscar}%")
                  ->orWhereHas('empresa', function($eq) use ($buscar) {
                      $eq->where('razon_social', 'LIKE', "%{$buscar}%")
                         ->orWhere('nombre_comercial', 'LIKE', "%{$buscar}%");
                  });
            });
        }

        $timbrados = $query->orderBy('fecha_vencimiento', 'desc')
                          ->paginate(15);

        // Actualizar estados automáticamente
        foreach ($timbrados as $timbrado) {
            $timbrado->actualizarEstado();
        }

        $empresas = $this->getEmpresasActivas();
        $tipos = TipoTimbrado::all();

        return view('timbrados.index', compact('timbrados', 'empresas', 'tipos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->middleware('permission:timbrados create');

        $empresas = $this->getEmpresasActivas();
        $tipos = TipoTimbrado::all();

        return view('timbrados.create', compact('empresas', 'tipos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->middleware('permission:timbrados create');

        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_tipo_timbrado' => 'required|exists:tipos_timbrados,id_tipo_timbrado',
            // Unique compuesto se valida manualmente o con Rule::unique->where...
            // Por simplicidad, validamos numero si existe para ese tipo
            'numero_timbrado' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = Timbrado::where('numero_timbrado', $value)
                                    ->where('id_tipo_timbrado', $request->id_tipo_timbrado)
                                    ->exists();
                    if ($exists) {
                        $fail('El número de timbrado ya existe para este tipo de documento.');
                    }
                },
            ],
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_inicio',
            'rango_inicio' => 'required|integer|min:1',
            'rango_fin' => 'required|integer|gt:rango_inicio',
            'establecimiento' => 'required|string|size:3',
            'punto_expedicion' => 'required|string|size:3',
            'observaciones' => 'nullable|string',
        ], [
            'id_empresa.required' => 'Debe seleccionar una empresa',
            'id_empresa.exists' => 'La empresa seleccionada no existe',
            'numero_timbrado.required' => 'El número de timbrado es obligatorio',
            'numero_timbrado.unique' => 'Este número de timbrado ya existe',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a la fecha de inicio',
            'rango_inicio.required' => 'El rango inicial es obligatorio',
            'rango_inicio.min' => 'El rango inicial debe ser al menos 1',
            'rango_fin.required' => 'El rango final es obligatorio',
            'rango_fin.gt' => 'El rango final debe ser mayor al rango inicial',
            'establecimiento.required' => 'El establecimiento es obligatorio',
            'establecimiento.size' => 'El establecimiento debe tener 3 dígitos',
            'punto_expedicion.required' => 'El punto de expedición es obligatorio',
            'punto_expedicion.size' => 'El punto de expedición debe tener 3 dígitos',
        ]);

        try {
            $timbrado = Timbrado::create($validated);

            Alert::success('Éxito', 'Timbrado creado correctamente');
            return redirect()->route('timbrados.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo crear el timbrado: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->middleware('permission:timbrados index');

        $timbrado = Timbrado::with(['empresa', 'ventas.cliente'])->findOrFail($id);
        $timbrado->actualizarEstado();

        // Estadísticas de uso
        $stats = [
            'total_facturas' => $timbrado->total_facturas,
            'facturas_usadas' => $timbrado->facturas_usadas,
            'facturas_disponibles' => $timbrado->facturas_disponibles,
            'porcentaje_uso' => $timbrado->porcentaje_uso,
            'dias_hasta_vencimiento' => $timbrado->dias_hasta_vencimiento,
        ];

        return view('timbrados.show', compact('timbrado', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $this->middleware('permission:timbrados edit');

        $timbrado = Timbrado::findOrFail($id);
        $empresas = $this->getEmpresasActivas();
        $tipos = TipoTimbrado::all();

        // No permitir editar si ya tiene facturas asociadas -> AHORA SI SE PERMITE CON RESTRICCIONES
        // if ($timbrado->ventas()->count() > 0) {
        //     Alert::warning('Advertencia', 'No se puede editar un timbrado que ya tiene facturas asociadas');
        //     return redirect()->route('timbrados.show', $id);
        // }

        return view('timbrados.edit', compact('timbrado', 'empresas', 'tipos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:timbrados edit');

        $timbrado = Timbrado::findOrFail($id);

        // Validar si tiene documentos asociados para aplicar restricciones
        $tieneDocumentos = false;
        $minDocumento = null;
        $maxDocumento = null;
        $minFecha = null;
        $maxFecha = null;

        // Determinar tipo de documento y buscar existentes
        if ($timbrado->id_tipo_timbrado == TipoTimbrado::FACTURA) {
            $tieneDocumentos = $timbrado->ventas()->exists();
            if ($tieneDocumentos) {
                 // Extraer numeros de factura (formato 001-001-0000001) para comparar rangos
                 // Asumimos que los numeros son secuenciales y el formato es estricto
                 $ventas = $timbrado->ventas()->get();
                 foreach($ventas as $venta) {
                     $parts = explode('-', $venta->factura_nro);
                     if(count($parts) == 3) {
                         $num = (int)$parts[2];
                         $minDocumento = is_null($minDocumento) ? $num : min($minDocumento, $num);
                         $maxDocumento = is_null($maxDocumento) ? $num : max($maxDocumento, $num);
                     }
                     
                     $fecha = Carbon::parse($venta->fecha_venta);
                     $minFecha = is_null($minFecha) ? $fecha : ($fecha->lt($minFecha) ? $fecha : $minFecha);
                     $maxFecha = is_null($maxFecha) ? $fecha : ($fecha->gt($maxFecha) ? $fecha : $maxFecha);
                 }
            }
        } elseif ($timbrado->id_tipo_timbrado == TipoTimbrado::NOTA_DEBITO) {
             // Verificar notas de debito (asumiendo relacion o tabla manual)
             // Como no tenemos relacion directa en modelo aun, usamos Query Builder
             $notas = DB::table('notas_debito')->where('id_timbrado', $id)->where('estado', '!=', 'ANULADO')->get();
             if ($notas->count() > 0) {
                 $tieneDocumentos = true;
                 foreach($notas as $nota) {
                     $parts = explode('-', $nota->nro_nota);
                     if(count($parts) == 3) {
                         $num = (int)$parts[2];
                         $minDocumento = is_null($minDocumento) ? $num : min($minDocumento, $num);
                         $maxDocumento = is_null($maxDocumento) ? $num : max($maxDocumento, $num);
                     }

                     $fecha = Carbon::parse($nota->fecha_emision);
                     $minFecha = is_null($minFecha) ? $fecha : ($fecha->lt($minFecha) ? $fecha : $minFecha);
                     $maxFecha = is_null($maxFecha) ? $fecha : ($fecha->gt($maxFecha) ? $fecha : $maxFecha);
                 }
             }
        }

        // if ($timbrado->ventas()->count() > 0) { ... } -> REMOVIDO

        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_tipo_timbrado' => 'required|exists:tipos_timbrados,id_tipo_timbrado',
            'numero_timbrado' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request, $id) {
                    $exists = Timbrado::where('numero_timbrado', $value)
                                    ->where('id_tipo_timbrado', $request->id_tipo_timbrado)
                                    ->where('id_timbrado', '!=', $id)
                                    ->exists();
                    if ($exists) {
                        $fail('El número de timbrado ya existe para este tipo de documento.');
                    }
                },
            ],
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_inicio',
            'rango_inicio' => 'required|integer|min:1',
            'rango_fin' => 'required|integer|gt:rango_inicio',
            'establecimiento' => 'required|string|size:3',
            'punto_expedicion' => 'required|string|size:3',
            'estado' => 'required|in:VIGENTE,VENCIDO,AGOTADO,CANCELADO',
            'observaciones' => 'nullable|string',
        ], [
            'id_empresa.required' => 'Debe seleccionar una empresa',
            'numero_timbrado.required' => 'El número de timbrado es obligatorio',
            'numero_timbrado.unique' => 'Este número de timbrado ya existe',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a la fecha de inicio',
            'rango_inicio.required' => 'El rango inicial es obligatorio',
            'rango_fin.required' => 'El rango final es obligatorio',
            'rango_fin.gt' => 'El rango final debe ser mayor al rango inicial',
            'estado.required' => 'El estado es obligatorio',
            'rango_fin.gt' => 'El rango final debe ser mayor al rango inicial',
            'estado.required' => 'El estado es obligatorio',
            'estado.in' => 'Estado inválido',
        ]);

        // VALIDACIÓN ADICIONAL DE INTEGRIDAD
        if ($tieneDocumentos) {
            // 1. Validar Rango
            if ($request->rango_inicio > $minDocumento) {
                 return back()->withInput()->withErrors(['rango_inicio' => "El rango inicial ({$request->rango_inicio}) no puede ser mayor al menor número de documento ya emitido ({$minDocumento})."]);
            }
            if ($request->rango_fin < $maxDocumento) {
                 return back()->withInput()->withErrors(['rango_fin' => "El rango final ({$request->rango_fin}) no puede ser menor al mayor número de documento ya emitido ({$maxDocumento})."]);
            }

            // 2. Validar Fechas (Fecha Inicio <= Menor Fecha Emision) y (Fecha Vencimiento >= Mayor Fecha Emision)
            $reqFechaInicio = Carbon::parse($request->fecha_inicio);
            $reqFechaVenc = Carbon::parse($request->fecha_vencimiento);

            if ($reqFechaInicio->gt($minFecha)) {
                 return back()->withInput()->withErrors(['fecha_inicio' => "La fecha de inicio no puede ser posterior a la fecha del primer documento emitido (" . $minFecha->format('d/m/Y') . ")."]);
            }
            if ($reqFechaVenc->lt($maxFecha)) {
                 return back()->withInput()->withErrors(['fecha_vencimiento' => "La fecha de vencimiento no puede ser anterior a la fecha del último documento emitido (" . $maxFecha->format('d/m/Y') . ")."]);
            }
        }

        try {
            $timbrado->update($validated);

            Alert::success('Éxito', 'Timbrado actualizado correctamente');
            return redirect()->route('timbrados.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo actualizar el timbrado: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->middleware('permission:timbrados destroy');

        $timbrado = Timbrado::find($id);

        if (!$timbrado) {
            Alert::error('Error', 'Timbrado no encontrado.');
            return redirect()->route('timbrados.index');
        }

        // No permitir eliminar si ya tiene facturas asociadas
        if ($timbrado->ventas()->count() > 0) {
            Alert::error('Error', 'No se puede eliminar un timbrado que ya tiene facturas asociadas.');
            return redirect()->route('timbrados.index');
        }

        try {
            $timbrado->delete();

            Alert::success('Éxito', 'Registro eliminado correctamente.');
            return redirect()->route('timbrados.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
            return redirect()->route('timbrados.index');
        }
    }

    /**
     * Cancelar un timbrado
     */
    public function cancelar($id)
    {
        $this->middleware('permission:timbrados edit');

        $timbrado = Timbrado::findOrFail($id);

        try {
            $timbrado->estado = 'CANCELADO';
            $timbrado->save();

            Alert::success('Éxito', 'Timbrado cancelado correctamente');
            return redirect()->route('timbrados.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cancelar el timbrado: ' . $e->getMessage());
            return redirect()->route('timbrados.index');
        }
    }

    /**
     * Obtener timbrados vigentes de una empresa (AJAX)
     */
    public function vigentesEmpresa(Request $request)
    {
        $idEmpresa = $request->id_empresa;

        if (!$idEmpresa) {
            return response()->json(['error' => 'ID de empresa requerido'], 400);
        }

        $timbrados = Timbrado::where('id_empresa', $idEmpresa)
                            ->vigentes()
                            ->get()
                            ->map(function($t) {
                                return [
                                    'id_timbrado' => $t->id_timbrado,
                                    'numero_timbrado' => $t->numero_timbrado,
                                    'fecha_vencimiento' => $t->fecha_vencimiento->format('d/m/Y'),
                                    'facturas_disponibles' => $t->facturas_disponibles,
                                ];
                            });

        return response()->json($timbrados);
    }

    /**
     * Obtener timbrados vigentes por sucursal (AJAX)
     * Busca la empresa de la sucursal y retorna sus timbrados vigentes
     */
    public function vigentesEmpresaPorSucursal(Request $request)
    {
        $idSucursal = $request->id_sucursal;

        if (!$idSucursal) {
            return response()->json(['error' => 'ID de sucursal requerido'], 400);
        }

        // Obtener la empresa de la sucursal
        $sucursal = DB::selectOne('SELECT id_empresa FROM sucursales WHERE id_sucursal = ?', [$idSucursal]);

        if (!$sucursal || !$sucursal->id_empresa) {
            return response()->json(['error' => 'Sucursal no encontrada o sin empresa asignada'], 404);
        }

        // Obtener timbrados vigentes de la empresa
        $query = Timbrado::where('id_empresa', $sucursal->id_empresa);

        if ($request->filled('id_tipo_timbrado')) {
            $query->where('id_tipo_timbrado', $request->id_tipo_timbrado);
        }

        $timbrados = $query->vigentes()
                            ->orderBy('fecha_vencimiento', 'asc')
                            ->get()
                            ->map(function($t) {
                                return [
                                    'id_timbrado' => $t->id_timbrado,
                                    'numero_timbrado' => $t->numero_timbrado,
                                    'fecha_vencimiento' => $t->fecha_vencimiento->format('d/m/Y'),
                                    'facturas_disponibles' => $t->facturas_disponibles,
                                ];
                            });

        return response()->json([
            'success' => true,
            'timbrados' => $timbrados,
            'id_empresa' => $sucursal->id_empresa
        ]);
    }

    /**
     * Obtener preview del siguiente número de factura de un timbrado (AJAX)
     */
    public function previewSiguienteNumero(Request $request)
    {
        $idTimbrado = $request->id_timbrado;

        if (!$idTimbrado) {
            return response()->json(['error' => 'ID de timbrado requerido'], 400);
        }

        $timbrado = Timbrado::find($idTimbrado);

        if (!$timbrado) {
            return response()->json(['error' => 'Timbrado no encontrado'], 404);
        }

        if (!$timbrado->estaVigente()) {
            return response()->json([
                'error' => 'Timbrado no vigente',
                'mensaje' => 'El timbrado seleccionado no está vigente',
                'estado' => $timbrado->estado
            ], 400);
        }

        $siguienteNumero = $timbrado->generarNumeroFactura();

        if (!$siguienteNumero) {
            return response()->json([
                'error' => 'No disponible',
                'mensaje' => 'No se puede generar número. Timbrado agotado o vencido.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'numero_factura' => $siguienteNumero,
            'timbrado' => $timbrado->numero_timbrado,
            'facturas_disponibles' => $timbrado->facturas_disponibles,
            'porcentaje_uso' => round($timbrado->porcentaje_uso, 1),
        ]);
    }
}
