<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class MarcaController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:marcas index')->only(['index']);
        $this->middleware('permission:marcas create')->only(['create', 'store']);
        $this->middleware('permission:marcas edit')->only(['edit', 'update']);
        $this->middleware('permission:marcas destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Obtener parámetros de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter'); // nuevo: filtro de estado del dropdown

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // Construir la query base con subconsulta para columna calculada estado
        $query = 'SELECT * FROM (
            SELECT *, CASE WHEN activo = true THEN \'Activo\' ELSE \'Inactivo\' END as estado 
            FROM marcas
        ) AS marcas_con_estado WHERE 1=1';
        $params = [];

        // Aplicar filtro de estado si existe (dropdown tiene prioridad)
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $estadoValue = ($estadoFilter === '1' || $estadoFilter === 'true') ? 'Activo' : 'Inactivo';
            $query .= ' AND estado = ?';
            $params[] = $estadoValue;
        }

        // Aplicar búsqueda por texto
        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $query .= ' AND id_marca = ?';
                $params[] = (int)$searchConfig['term'];
            } else {
                // Búsqueda por descripción, ID o estado (con OR para que sea opcional)
                $buscarTerm = $searchConfig['term'];
                $query .= ' AND (descripcion ILIKE ? OR cast(id_marca as text) ILIKE ? OR estado ILIKE ?)';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
            }
        }

        $query .= ' ORDER BY id_marca DESC';

        // Ejecutar la consulta
        $marcas = DB::select($query, $params);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($marcas);             // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($marcas, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $marcas = new LengthAwarePaginator(
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
            return view('marcas.table')->with('marcas', $marcas);
        }

        return view('marcas.index')
            ->with('marcas', $marcas)
            ->with('estadoFilter', $estadoFilter);
    }
    public function create()
    {
        return view('marcas.create');
    }
    public function store(Request $request)
    {
        //campos recibidos del formulario
        $input = $request->all();
        //validacion de los campos
        $validacion = Validator::make(
            $input,
            [
                'descripcion' => 'required'
            ],
            [
                'descripcion.required' => 'El campo descripcion es obligatorio'
            ]
        );
        if ($validacion->fails()) {
            return back()->withErrors($validacion)->withInput();
        }

        // Validar que la descripción no esté duplicada
        $existe = DB::selectOne(
            'SELECT * FROM marcas WHERE LOWER(descripcion) = LOWER(?)',
            [$input['descripcion']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una marca con esta descripción.'])->withInput();
        }

        //insertar proveedores en la base de datos
        DB::insert(
            'INSERT INTO marcas (descripcion) values (?)',
            [
                $input['descripcion']
            ]
        );
        Alert::toast('Marca registrada con exito', 'success');
        return redirect()->route('marcas.index');
    }
    public function edit($id)
    {
        $marca = DB::selectOne('SELECT * FROM marcas WHERE id_marca = ?', [$id]);
        if (empty($marca)) {
            Alert::toast('Marca no encontrada', 'error');
            return redirect()->route('marcas.index');
        }
        return view('marcas.edit')->with('marcas', $marca);
    }
    public function update(Request $request, $id)
    {
        $marca = DB::selectOne('SELECT * FROM marcas WHERE id_marca = ?', [$id]);
        if (empty($marca)) {
            Alert::toast('Marca no encontrada', 'error');
            return redirect()->route('marcas.index');
        }
        $input = $request->all();
        $validacion = Validator::make(
            $input,
            [
                'descripcion' => 'required'
            ],
            [
                'descripcion.required' => 'El campo descripcion es obligatorio'
            ]
        );
        if ($validacion->fails()) {
            return back()->withErrors($validacion)->withInput();
        }

        // Validar que la descripción no esté duplicada (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM marcas WHERE LOWER(descripcion) = LOWER(?) AND id_marca != ?',
            [$input['descripcion'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una marca con esta descripción.'])->withInput();
        }

        DB::update(
            'UPDATE marcas
       SET descripcion = ?
       WHERE id_marca = ?',
            [
                $input['descripcion'],
                $id
            ]
        );
        Alert::toast('Marca actualizada con exito', 'success');
        return redirect()->route('marcas.index');
    }
    public function destroy($id)
    {
        $marca = DB::selectOne('SELECT * FROM marcas WHERE id_marca = ?', [$id]);
        if (empty($marca)) {
            Alert::error('Error', 'Marca no encontrada.');
            return redirect()->route('marcas.index');
        }

        try {
            // Cambiar el estado activo (toggle)
            $nuevoEstado = !$marca->activo;
            DB::update('UPDATE marcas SET activo = ? WHERE id_marca = ?', [$nuevoEstado, $id]);
            
            $mensaje = $nuevoEstado ? 'Marca activada correctamente.' : 'Marca inactivada correctamente.';
            Alert::success('Éxito', $mensaje);
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }

        return redirect()->route('marcas.index');
    }
}
