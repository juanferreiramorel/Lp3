<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartamentoController extends AppBaseController
{
    public function __construct()
    {
        // validar que el usuario este autenticado
        $this->middleware('auth');
        // validar permisos para cada accion
        $this->middleware('permission:departamentos index')->only(['index']);
        $this->middleware('permission:departamentos create')->only(['create', 'store']);
        $this->middleware('permission:departamentos edit')->only(['edit', 'update']);
        $this->middleware('permission:departamentos destroy')->only(['destroy']);
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
                $departamentos = DB::select(
                    'SELECT * FROM departamentos
                    WHERE id_departamento = ?
                    ORDER BY id_departamento DESC',
                    [(int)$searchConfig['term']]
                );
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];
                $departamentos = DB::select(
                    'SELECT * FROM departamentos
                    WHERE descripcion ILIKE ?
                    OR cast(id_departamento as text) ILIKE ?
                    ORDER BY id_departamento DESC',
                    ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%']
                );
            }
        } else {
            $departamentos = DB::select('SELECT * FROM departamentos ORDER BY id_departamento DESC');
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($departamentos);      // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($departamentos, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $departamentos = new LengthAwarePaginator(
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
            return view('departamentos.table')->with('departamentos', $departamentos);
        }

        return view('departamentos.index')->with('departamentos', $departamentos);
    }

    public function create()
    {
        return view('departamentos.create');
    }
    
    public function store(Request $request)
    {
        $input = $request->all();
        // validar los datos del formulario
        $validator = Validator::make(
            $input,
            [
                'descripcion' => 'required',
            ],
            [
                'descripcion.required' => 'El campo descripción es obligatorio.',
            ]
        );

        // Imprimir el error si la validacion falla
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validar que la descripción no esté duplicada
        $existe = DB::selectOne(
            'SELECT * FROM departamentos WHERE LOWER(descripcion) = LOWER(?)',
            [$input['descripcion']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un departamento con esta descripción.'])->withInput();
        }

        // Si la validación pasa, guardar el nuevo departamento utilizando la función insert de la base de datos
        DB::insert('insert into departamentos (descripcion) values (?)',
            [
                $input['descripcion']
            ]
        );

        ## Imprimir mensaje de éxito y redirigir a la vista index
        Alert::success('Éxito', 'Departamento creado correctamente.');
        return redirect(route('departamentos.index'));
    }

    public function edit($id)
    {
        // Obtener el departamento por su ID utilizando la función select de la base de datos segun $id recibido
        $departamento = DB::selectOne('select * from departamentos where id_departamento = ?', [$id]);

        // Verificar si el departamento existe y no está vacío
        if (empty($departamento)) {
            Alert::error('Error', 'El departamento no fue encontrado.');
            // Redirigir a la vista index si el departamento no existe
            return redirect()->route('departamentos.index');
        }

        // Retornar la vista con el formulario de edición
        return view('departamentos.edit')->with('departamento', $departamento);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        // Validar los datos del formulario
        $validator = Validator::make(
            $input,
            [
                'descripcion' => 'required',
            ],
            [
                'descripcion.required' => 'El campo descripción es obligatorio.',
            ]
        );

        // Imprimir el error si la validacion falla
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validar que la descripción no esté duplicada (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM departamentos WHERE LOWER(descripcion) = LOWER(?) AND id_departamento != ?',
            [$input['descripcion'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un departamento con esta descripción.'])->withInput();
        }

        // Actualizar el departamento utilizando la función update de la base de datos
        DB::update('update departamentos set descripcion = ? where id_departamento = ?',
            [
                $input['descripcion'],
                $id
            ]
        );

        // Imprimir mensaje de éxito y redirigir a la vista index
        Alert::success('Éxito', 'Departamento actualizado correctamente.');
        return redirect(route('departamentos.index'));
    }

    public function destroy($id)
    {
        // Obtener el departamento por su ID
        $departamentos = DB::selectOne('select * from departamentos where id_departamento = ?', [$id]);
        // Verificar si el departamento existe y no está vacío
        if (empty($departamentos)) {
            Alert::error('Error', 'Departamento no encontrado.');
            return redirect()->route('departamentos.index');
        }

        try {
            // Eliminar el departamento utilizando la función delete de la base de datos
            DB::delete('delete from departamentos where id_departamento = ?', [$id]);
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
        }

        return redirect(route('departamentos.index'));
    }

    /**
     * Devuelve un listado simple de departamentos para peticiones AJAX.
     */
    public function listadoSimple()
    {
        $departamentos = DB::table('departamentos')
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_departamento');

        return response()->json($departamentos);
    }
}
