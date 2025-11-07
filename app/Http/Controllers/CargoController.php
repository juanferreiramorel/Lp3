<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class CargoController extends Controller
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
        # Ejemplo query builder
        #$cargos = DB::table('cargos')->get();
        # Ejemplo con sql puro a utilizar
        if ($buscar) {
            $cargos = DB::select('select * from cargos where descripcion ilike ?', ['%' . $buscar . '%']);
        } else {
            $cargos = DB::select('select * from cargos');
        }

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
            return view('cargos.table')->with('cargos', $cargos);
        }

        return view('cargos.index')->with('cargos', $cargos);
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
        DB::insert('insert into cargos (descripcion) values (?)',
            [
                $input['descripcion']
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
            Alert::toast('El cargo no fue encontrado.', 'error');
            return redirect()->route('cargos.index');
        }

        try {
            // Eliminar el cargo utilizando la función delete de la base de datos
            DB::delete('delete from cargos where id_cargo = ?', [$id]);
            Alert::toast('El cargo fue eliminado con éxito.', 'success');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se puede eliminar este cargo porque está siendo utilizado en otros registros.');
        }

        return redirect(route('cargos.index'));
    }
}
