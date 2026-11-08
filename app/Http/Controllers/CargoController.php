<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class CargoController extends AppBaseController
{
    public function __construct()
    {
        // validar que el usuario este autenticado
        $this->middleware('auth');
        // validar permisos para cada accion
        $this->middleware('permission:cargos index')->only(['index']);
        $this->middleware('permission:cargos create')->only(['create', 'store']);
        $this->middleware('permission:cargos edit')->only(['edit', 'update']);
        $this->middleware('permission:cargos destroy')->only(['destroy']);
    }
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        $query = 'SELECT * FROM cargos WHERE 1=1';
        $params = [];

        // Aplicar filtro de estado
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $query .= ' AND estado = ?';
            $params[] = ($estadoFilter === '1' || $estadoFilter === 'true') ? true : false;
        }

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $query .= ' AND id_cargo = ?';
                $params[] = (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];
                $query .= ' AND (descripcion ILIKE ? OR cast(id_cargo as text) ILIKE ?)';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
            }
        }

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'id_cargo', 'first' => 'desc'],
            'descripcion' => ['label' => 'Descripcion', 'sort' => 'descripcion'],
            'estado' => ['label' => 'Estado', 'sort' => 'estado'],
            'acciones' => ['label' => 'Acción', 'width' => '140px'],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        $query .= " ORDER BY {$sort->orderBy()}";

        $cargos = DB::select($query, $params);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($cargos);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($cargos, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $cargos = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // si la accion es buscardor entonces significa que se debe recargar mediante ajax la tabla
        if ($request->ajax()) {
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('cargos.table')->with('cargos', $cargos)->with('columns', $columns)->with('sort', $sort);
        }

        return view('cargos.index')->with('cargos', $cargos)->with('columns', $columns)->with('sort', $sort);
    }

    public function create()
    {
        // retornar la vista con el formulario de cargos create
        return view('cargos.create');
    }
    public function store(Request $request)
    {
        // recibir los datos del formulario
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
            'SELECT * FROM cargos WHERE LOWER(descripcion) = LOWER(?)',
            [$input['descripcion']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un cargo con esta descripción.'])->withInput();
        }

        // Si la validación pasa, guardar el nuevo cargo utilizando la función insert de la base de datos
        DB::insert('insert into cargos (descripcion, estado) values (?, ?)',
            [
                $input['descripcion'],
                true
            ]
        );

        ## Imprimir mensaje de éxito y redirigir a la vista index
        Alert::toast('El cargo fue creado con éxito.', 'success');
        return redirect(route('cargos.index'));
    }

    public function edit($id)
    {
        // Obtener el cargo por su ID utilizando la función select de la base de datos segun $id recibido
        $cargo = DB::selectOne('select * from cargos where id_cargo = ?', [$id]);

        // Verificar si el cargo existe y no está vacío
        if (empty($cargo)) {
            Alert::toast('El cargo no fue encontrado.', 'error');
            // Redirigir a la vista index si el cargo no existe
            return redirect()->route('cargos.index');
        }

        // Retornar la vista con el formulario de edición
        return view('cargos.edit')->with('cargo', $cargo);
    }

    public function update(Request $request, $id)
    {
        // Actualizar el cargo utilizando la función update de la base de datos
        $input = $request->all();
        // Obtener el cargo por su ID utilizando la función select de la base de datos segun $id recibido
        $cargo = DB::selectOne('select * from cargos where id_cargo = ?', [$id]);
        // Verificar si el cargo existe y no está vacío
        if (empty($cargo)) {
            Alert::toast('El cargo no fue encontrado.', 'error');
            // Redirigir a la vista index si el cargo no existe
            return redirect()->route('cargos.index');
        }

        // Validar los datos de entrada utilizando la clase Validator de Laravel
        $validator = Validator::make(
            $input,
            [
                'descripcion' => 'required',
            ],
            [
                'descripcion.required' => 'El campo descripción es obligatorio.',
            ]
        );

        // Imprimir los errores de validación si existen
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validar que la descripción no esté duplicada (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM cargos WHERE LOWER(descripcion) = LOWER(?) AND id_cargo != ?',
            [$input['descripcion'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un cargo con esta descripción.'])->withInput();
        }

        DB::update('update cargos set descripcion = ? where id_cargo = ?',
            [
                $input['descripcion'],
                $id
            ]
        );

        // Imprimir mensaje de éxito y redirigir a la vista index
        Alert::toast('El cargo fue actualizado con éxito.', 'success');
        return redirect(route('cargos.index'));
    }

    public function destroy($id)
    {
        // Obtener el cargo por su ID
        $cargo = DB::selectOne('select * from cargos where id_cargo = ?', [$id]);
        // Verificar si el cargo existe y no está vacío
        if (empty($cargo)) {
            Alert::error('Error', 'Cargo no encontrado.');
            return redirect()->route('cargos.index');
        }

        try {
            // Soft delete: cambiar el estado
            $nuevoEstado = isset($cargo->estado) ? !$cargo->estado : false; 
            
            DB::update('UPDATE cargos SET estado = ? WHERE id_cargo = ?', [$nuevoEstado, $id]);
            
            $mensaje = $nuevoEstado ? 'Cargo activado correctamente.' : 'Cargo inactivado correctamente.';
            Alert::toast($mensaje, 'success');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }

        return redirect(route('cargos.index'));
    }
}
