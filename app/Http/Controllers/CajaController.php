<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class CajaController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:cajas index')->only(['index']);
        $this->middleware('permission:cajas create')->only(['create', 'store']);
        $this->middleware('permission:cajas edit')->only(['edit', 'update']);
        $this->middleware('permission:cajas destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Obtener parámetros de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter'); // nuevo: filtro de estado del dropdown
        $sucursalFilter = $request->get('sucursal_filter'); // nuevo: filtro de sucursal

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // Construir la query base con subconsulta para columna calculada estado
        $query = 'SELECT * FROM (
            SELECT c.*, s.descripcion as sucursal, 
            CASE WHEN c.activo = true THEN \'Activo\' ELSE \'Inactivo\' END as estado 
            FROM cajas c
            JOIN sucursales s ON c.id_sucursal = s.id_sucursal
        ) AS cajas_con_estado WHERE 1=1';
        $params = [];

        // Aplicar filtro de estado si existe (dropdown tiene prioridad)
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $estadoValue = ($estadoFilter === '1' || $estadoFilter === 'true') ? 'Activo' : 'Inactivo';
            $query .= ' AND estado = ?';
            $params[] = $estadoValue;
        }

        // Aplicar filtro de sucursal si existe
        if ($sucursalFilter !== null && $sucursalFilter !== '') {
            $query .= ' AND id_sucursal = ?';
            $params[] = (int)$sucursalFilter;
        }

        // Aplicar búsqueda por texto
        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $query .= ' AND id_caja = ?';
                $params[] = (int)$searchConfig['term'];
            } else {
                // Búsqueda por descripción, ID o estado (con OR para que sea opcional)
                $buscarTerm = $searchConfig['term'];
                $query .= ' AND (descripcion ILIKE ? OR sucursal ILIKE ? OR cast(punto_expedicion as text) ILIKE ? OR cast(ultima_factura_impresa as text) ILIKE ? OR cast(id_caja as text) ILIKE ? OR estado ILIKE ?)';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
            }
        }

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        // 'field' says which property of the row holds the value and 'format'
        // how to print it; both are only read by the export.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'id_caja', 'field' => 'id_caja', 'first' => 'desc'],
            'descripcion' => ['label' => 'Descripcion', 'sort' => 'descripcion'],
            'sucursal' => ['label' => 'Sucursal', 'sort' => 'sucursal'],
            // The subquery already resolves the flag into 'Activo'/'Inactivo'.
            'estado' => ['label' => 'Estado', 'sort' => 'estado'],
            'acciones' => ['label' => 'Accion', 'width' => '140px', 'export' => false],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        $seleccion = $query . ' ORDER BY ' . $sort->orderBy();

        // Exports run the same query without the page window, so the file
        // carries every row the current filters match, not just this page.
        if ($export = \App\Support\TableExport::fromRequest($request, $columns)) {
            return $export->download(fn () => DB::select($seleccion, $params), 'cajas');
        }

        // Ejecutar la consulta
        $cajas = DB::select($seleccion, $params);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($cajas);              // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($cajas, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $cajas = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // Obtener lista de sucursales para el filtro
        $sucursales = $this->getSucursalesPermitidas();

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('cajas.table')
                ->with('cajas', $cajas)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        return view('cajas.index')
            ->with('cajas', $cajas)
            ->with('estadoFilter', $estadoFilter)
            ->with('sucursalFilter', $sucursalFilter)
            ->with('sucursales', $sucursales)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }
    public function create()
    {
        //Obtener sucursales
        $sucursales = $this->getSucursalesPermitidas();
        return view('cajas.create')->with('sucursales', $sucursales);
    }
    public function store(Request $request)
    {
        $input = $request->all();

        //Validar los datos
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'punto_expedicion' => 'required',
            'ultima_factura_impresa' => 'required',
        ], [
            'descripcion.required' => 'La descripcion es obligatoria',
            'id_sucursal.required' => 'La sucursal es obligatoria',
            'id_sucursal.exists' => 'La sucursal no existe',
            'punto_expedicion.required' => 'El punto de expedicion es obligatorio',
            'ultima_factura_impresa.required' => 'La ultima factura impresa es obligatoria',
        ]);
        //si la validacion falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que la descripción no esté duplicada en la misma sucursal
        $existe = DB::selectOne(
            'SELECT * FROM cajas WHERE LOWER(descripcion) = LOWER(?) AND id_sucursal = ?',
            [$input['descripcion'], $input['id_sucursal']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una caja con esta descripción en la sucursal seleccionada.'])->withInput();
        }

        //Insertar el nuevo producto en la Base de datos
        DB::insert(
            'INSERT INTO cajas (descripcion, id_sucursal, punto_expedicion, ultima_factura_impresa) VALUES (?, ?, ?, ?)',
            [
                $input['descripcion'],
                $input['id_sucursal'],
                $input['punto_expedicion'],
                $input['ultima_factura_impresa']
            ]
        );
        //Redirigir a la lista de cajas con un mesaje de exito
        Alert::toast('Caja creada con éxito', 'success');
        //Flash::success('Caja creada con exito');
        return redirect()->route('cajas.index');
    }
    public function edit($id)
    {
        $cajas = DB::selectOne('SELECT * FROM cajas WHERE id_caja = ?', [$id]); //SELECTONE devuelve un objeto()
        if (empty($cajas)) {
            Alert::toast('Caja no encontrada', 'error');
            return redirect()->route('cajas.index');
        }
        //Obtener sucursales
        $sucursales = $this->getSucursalesPermitidas();
        return view('cajas.edit')->with('cajas', $cajas)->with('sucursales', $sucursales)->with(
            'cajas',
            $cajas
        );
    }
    public function update(Request $request, $id)
    {
        $input = $request->all();
        //Obtener el producto de la base de datos  1 solo valor utilizando selectOne
        $cajas = DB::selectOne('SELECT * FROM cajas WHERE id_caja = ?', [$id]);
        //Validar si el producto no existe, redirigir con un mesaje de error
        if (empty($cajas)) {
            Alert::toast('Caja no encontrada', 'error');
            return redirect()->route('cajas.index');
        }
        //Validar los datos
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'punto_expedicion' => 'required',
            'ultima_factura_impresa' => 'required',
        ], [
            'descripcion.required' => 'La descripcion es obligatoria',
            'id_sucursal.required' => 'La sucursal es obligatoria',
            'id_sucursal.exists' => 'La sucursal no existe',
            'punto_expedicion.required' => 'El punto de expedicion es obligatorio',
            'ultima_factura_impresa.required' => 'La ultima factura impresa es obligatoria',
        ]);
        //si la validacion falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que la descripción no esté duplicada en la misma sucursal (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM cajas WHERE LOWER(descripcion) = LOWER(?) AND id_sucursal = ? AND id_caja != ?',
            [$input['descripcion'], $input['id_sucursal'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una caja con esta descripción en la sucursal seleccionada.'])->withInput();
        }

        //Actualizar el producto en la Base de datos
        DB::update(
            'UPDATE cajas SET descripcion = ?, id_sucursal = ?, punto_expedicion = ?, ultima_factura_impresa = ? WHERE id_caja = ?',
            [
                $input['descripcion'],
                $input['id_sucursal'],
                $input['punto_expedicion'],
                $input['ultima_factura_impresa'],
                $id
            ]
        );
        //Redirigir a la lista de cajas con un mesaje de éxito
        Alert::toast('Caja actualizada con éxito', 'success');
        //Flash::success('Caja actualizada con éxito');
        return redirect()->route('cajas.index');
    }
    public function destroy($id)
    {
        //Validar si la caja existe
        $caja = DB::selectOne('SELECT * FROM cajas WHERE id_caja = ?', [$id]);
        if (empty($caja)) {
            Alert::toast('Caja no encontrada', 'error');
            return redirect()->route('cajas.index');
        }

        // Si se intenta inactivar, verificar que no tenga una apertura activa
        if ($caja->activo) {
            $apertura_activa = DB::selectOne(
                "SELECT * FROM apertura_cierre_cajas WHERE id_caja = ? AND estado = 'ABIERTA'",
                [$id]
            );

            if ($apertura_activa) {
                Alert::error('Error', 'No se puede inactivar esta caja porque tiene una apertura activa. Debe cerrar la caja primero.');
                return redirect()->route('cajas.index');
            }
        }

        try {
            // Cambiar el estado activo (toggle)
            $nuevoEstado = !$caja->activo;
            DB::update('UPDATE cajas SET activo = ? WHERE id_caja = ?', [$nuevoEstado, $id]);
            
            $mensaje = $nuevoEstado ? 'Caja activada correctamente.' : 'Caja inactivada correctamente.';
            Alert::success('Éxito', $mensaje);
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }

        return redirect(route('cajas.index'));
    }
}
