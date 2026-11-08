<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // validar permisos para cada accion
        $this->middleware('permission:permissions index')->only(['index']);
        $this->middleware('permission:permissions create')->only(['create', 'store']);
        $this->middleware('permission:permissions edit')->only(['edit', 'update']);
        $this->middleware('permission:permissions destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Obtener el término de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Construir consulta con filtros de búsqueda por todos los campos visibles
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        // 'field' says which property of the row holds the value and 'format'
        // how to print it; both are only read by the export.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'id', 'first' => 'desc'],
            'name' => ['label' => 'Permisos', 'sort' => 'name'],
            'acciones' => ['label' => 'Action', 'width' => '140px', 'export' => false],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        // The listing query without its page window, so the export and the
        // page both read the same rows under the same filters.
        $params = [];

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                $seleccion = 'SELECT * FROM permissions WHERE id = ? ORDER BY ' . $sort->orderBy();
                $params = [(int)$searchConfig['term']];
            } else {
                $buscarTerm = $searchConfig['term'];
                $seleccion = 'SELECT * FROM permissions
                    WHERE CAST(id AS TEXT) ILIKE ?
                    OR name ILIKE ?
                    OR guard_name ILIKE ?
                    ORDER BY ' . $sort->orderBy();
                $params = [
                    '%' . $buscarTerm . '%',
                    '%' . $buscarTerm . '%',
                    '%' . $buscarTerm . '%'
                ];
            }
        } else {
            $seleccion = 'SELECT * FROM permissions ORDER BY ' . $sort->orderBy();
        }

        // Exports run the same query without the page window, so the file
        // carries every row the current filters match, not just this page.
        if ($export = \App\Support\TableExport::fromRequest($request, $columns)) {
            return $export->download(fn () => DB::select($seleccion, $params), 'permisos');
        }

        $permisos = DB::select($seleccion, $params);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($permisos);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($permisos, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $permisos = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('permissions.table')
                ->with('permisos', $permisos)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        return view('permissions.index')
            ->with('permisos', $permisos)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        // Validar que el campo 'name' no esté vacío
        $validacion = Validator::make($input, [
            'name' => 'required|unique:permissions,name',
        ], [
            'name.required' => 'El campo Permiso es obligatorio.',
            'name.unique' => 'El permiso ya existe en la base de datos.',
        ]);

        // Si la validación falla, redirigir de vuelta con errores
        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Insertar el nuevo permiso en la base de datos
        DB::insert('INSERT INTO permissions (name, guard_name) VALUES (?, ?)', [
            $input['name'],
            $input['guard_name'],
        ]);

        // Mostrar un mensaje de confirmación
        Alert::toast('Permiso creado correctamente', 'success');

        // Redirigir al listado de permisos
        return redirect()->route('permissions.index');
    }

    public function edit($id) 
    {
        $permisos = DB::selectOne('SELECT * FROM permissions WHERE id = ?', [$id]);
        // Verificar si se encontró el permiso
        if (empty($permisos)) {
            Alert::toast('Permiso no encontrado', 'error');
            return redirect()->route('permissions.index');
        }

        return view('permissions.edit')->with('permisos', $permisos);
    }

    public function update(Request $request, $id) 
    {
        $input = $request->all();

        $permisos = DB::selectOne('SELECT * FROM permissions WHERE id = ?', [$id]);
        // Verificar si se encontró el permiso
        if (empty($permisos)) {
            Alert::toast('Permiso no encontrado', 'error');
            return redirect()->route('permissions.index');
        }

        // Validar que el campo 'name' no esté vacío y sea único, excluyendo el permiso actual
        $validacion = Validator::make($input, [
            'name' => 'required|unique:permissions,name,' . $id,
        ], [
            'name.required' => 'El campo Permiso es obligatorio.',
            'name.unique' => 'El permiso ya existe en la base de datos.',
        ]);

        // Si la validación falla, redirigir de vuelta con errores
        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Actualizar el permiso en la base de datos
        DB::update('UPDATE permissions SET name = ?, guard_name = ? WHERE id = ?', [
            $input['name'],
            $input['guard_name'],
            $id,
        ]);

        // Mostrar un mensaje de confirmación
        Alert::toast('Permiso actualizado correctamente', 'success');

        // Redirigir al listado de permisos
        return redirect()->route('permissions.index');
    }

    public function destroy($id)
    {
        $permisos = DB::selectOne('SELECT * FROM permissions WHERE id = ?', [$id]);
        // Verificar si se encontró el permiso
        if (empty($permisos)) {
            Alert::error('Error', 'Permiso no encontrado.');
            return redirect()->route('permissions.index');
        }

        // Utilizar un bloque try-catch para manejar posibles errores al eliminar
        try{
            // Eliminar el permiso de la base de datos
            DB::delete('DELETE FROM permissions WHERE id = ?', [$id]);
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        }catch(\Exception $e){
            // Manejar el error si el permiso está asignado a algún rol
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
            return redirect()->route('permissions.index');
        }

        // Redirigir al listado de permisos
        return redirect()->route('permissions.index');
    }
}
