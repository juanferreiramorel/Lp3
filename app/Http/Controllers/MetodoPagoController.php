<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MetodoPago;
use App\Models\TipoMetodoPago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class MetodoPagoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:metodo_pagos index')->only(['index']);
        $this->middleware('permission:metodo_pagos create')->only(['create', 'store']);
        $this->middleware('permission:metodo_pagos edit')->only(['edit', 'update']);
        $this->middleware('permission:metodo_pagos destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Obtener parámetros de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter');
        $tipoFilter = $request->get('tipo_filter');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // Construir la query base
        $query = 'SELECT mp.*, tmp.descripcion as tipo_descripcion 
                  FROM metodo_pagos mp
                  LEFT JOIN tipo_metodo_pagos tmp ON mp.id_tipo_metodo_pago = tmp.id_tipo_metodo_pago
                  WHERE 1=1';
        $params = [];

        // Aplicar filtro de estado
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $query .= ' AND mp.estado = ?';
            $params[] = ($estadoFilter === '1' || $estadoFilter === 'true') ? true : false;
        }

        // Aplicar filtro de tipo
        if ($tipoFilter !== null && $tipoFilter !== '') {
            $query .= ' AND mp.id_tipo_metodo_pago = ?';
            $params[] = (int)$tipoFilter;
        }

        // Aplicar búsqueda por texto
        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                $query .= ' AND mp.id_metodo_pago = ?';
                $params[] = (int)$searchConfig['term'];
            } else {
                $buscarTerm = $searchConfig['term'];
                $query .= ' AND (mp.descripcion ILIKE ? OR CAST(mp.id_metodo_pago AS TEXT) ILIKE ? OR tmp.descripcion ILIKE ?)';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
            }
        }

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'mp.id_metodo_pago', 'first' => 'desc'],
            'descripcion' => ['label' => 'Descripción', 'sort' => 'mp.descripcion'],
            'tipo' => ['label' => 'Tipo', 'sort' => 'tmp.descripcion'],
            'estado' => ['label' => 'Estado', 'sort' => 'mp.estado'],
            'acciones' => ['label' => 'Acción', 'width' => '140px'],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        $query .= " ORDER BY {$sort->orderBy()}";

        $metodos = \Illuminate\Support\Facades\DB::select($query, $params);

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $total = count($metodos);
        $items = array_slice($metodos, ($page - 1) * $perPage, $perPage);

        $metodosPaginados = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Transformar items (stdClass) a modelos para mantener compatibilidad con la vista
        // O adaptar la vista para usar objetos estándar. 
        // La vista usa $metodo->tipo->descripcion. Si uso DB::select, $metodo->tipo no existirá.
        // He incluido tmp.descripcion as tipo_descripcion en la query.
        // Adaptaremos la vista para usar $metodo->tipo_descripcion si está disponible, o mantendremos el modelo si usamos Eloquent.
        // Dado el estilo de CajaController, usan DB::select. Adaptaré la respuesta para que sea compatible o cambiaré a Eloquent si es más fácil.
        // Pero CajaController usa DB::select, así que seguiré ese patrón y ajustaré el objeto.
        
        // Ajuste: La vista table.blade.php espera $metodo->tipo->descripcion o usa $metodo->tipo_descripcion si cambio la vista.
        // Si uso DB::select, devolverá objetos stdClass.
        // Voy a simular esa estructura.
        
        $itemsHydrated = collect($items)->map(function($item) {
             $item->tipo = (object)['descripcion' => $item->tipo_descripcion];
             return $item;
        });

        $metodosPaginados = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsHydrated, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $tipos = TipoMetodoPago::where('estado', true)->pluck('descripcion', 'id_tipo_metodo_pago');

        if ($request->ajax()) {
            return view('metodo_pagos.table')->with('metodos', $metodosPaginados)->with('columns', $columns)->with('sort', $sort);
        }

        return view('metodo_pagos.index')
            ->with('metodos', $metodosPaginados)
            ->with('tipos', $tipos)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }

    public function create()
    {
        $tipos = TipoMetodoPago::where('estado', true)->pluck('descripcion', 'id_tipo_metodo_pago');
        return view('metodo_pagos.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:100',
            'id_tipo_metodo_pago' => 'required|exists:tipo_metodo_pagos,id_tipo_metodo_pago'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        MetodoPago::create([
            'descripcion' => $input['descripcion'],
            'id_tipo_metodo_pago' => $input['id_tipo_metodo_pago'],
            'estado' => true,
            'id_tipo_transaccion' => 0 // Default as per requirements
        ]);

        Alert::success('Éxito', 'Método de pago creado correctamente.');
        return redirect()->route('metodo_pagos.index');
    }

    public function edit($id)
    {
        $metodo = \Illuminate\Support\Facades\DB::selectOne('SELECT * FROM metodo_pagos WHERE id_metodo_pago = ?', [$id]);
        
        if (empty($metodo)) {
            Alert::error('Error', 'Método de pago no encontrado');
            return redirect()->route('metodo_pagos.index');
        }

        $tipos = TipoMetodoPago::where('estado', true)->pluck('descripcion', 'id_tipo_metodo_pago');
        return view('metodo_pagos.edit', compact('metodo', 'tipos'));
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:100',
            'id_tipo_metodo_pago' => 'required|exists:tipo_metodo_pagos,id_tipo_metodo_pago'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $metodo = \Illuminate\Support\Facades\DB::selectOne('SELECT * FROM metodo_pagos WHERE id_metodo_pago = ?', [$id]);
        
        if (empty($metodo)) {
             Alert::error('Error', 'Método de pago no encontrado');
             return redirect()->route('metodo_pagos.index');
        }

        \Illuminate\Support\Facades\DB::update(
            'UPDATE metodo_pagos SET descripcion = ?, id_tipo_metodo_pago = ? WHERE id_metodo_pago = ?',
            [$input['descripcion'], $input['id_tipo_metodo_pago'], $id]
        );

        Alert::success('Éxito', 'Método de pago actualizado correctamente.');
        return redirect()->route('metodo_pagos.index');
    }

    public function destroy($id)
    {
        $metodo = \Illuminate\Support\Facades\DB::selectOne('SELECT * FROM metodo_pagos WHERE id_metodo_pago = ?', [$id]);

        if (empty($metodo)) {
            Alert::error('Error', 'Método de pago no encontrado');
            return redirect()->route('metodo_pagos.index');
        }

        try {
            $nuevoEstado = !$metodo->estado;
            \Illuminate\Support\Facades\DB::update('UPDATE metodo_pagos SET estado = ? WHERE id_metodo_pago = ?', [$nuevoEstado, $id]);
            
            $mensaje = $nuevoEstado ? 'Método activado correctamente.' : 'Método inactivado correctamente.';
            Alert::success('Éxito', $mensaje);
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }

        return redirect()->route('metodo_pagos.index');
    }
}
