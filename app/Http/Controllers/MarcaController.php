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
        // Obtener el término de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $marcas = DB::select(
                    'SELECT * FROM marcas
                    WHERE id_marca = ?
                    ORDER BY id_marca DESC',
                    [(int)$searchConfig['term']]
                );
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];
                $marcas = DB::select(
                    'SELECT * FROM marcas
                    WHERE descripcion ILIKE ?
                    OR cast(id_marca as text) ILIKE ?
                    ORDER BY id_marca DESC',
                    ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%']
                );
            }
        } else {
            $marcas = DB::SELECT('SELECT * FROM marcas ORDER BY id_marca DESC');
        }

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

        return view('marcas.index')->with('marcas', $marcas);
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
            DB::delete('DELETE FROM marcas WHERE id_marca = ?', [$id]);
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
        }

        return redirect()->route('marcas.index');
    }
}
