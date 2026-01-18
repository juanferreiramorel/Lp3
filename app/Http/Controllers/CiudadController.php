<?php

namespace App\Http\Controllers;

use App\Exports\CiudadExport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;

class CiudadController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ciudades index')->only(['index']);
        $this->middleware('permission:ciudades create')->only(['create', 'store']);
        $this->middleware('permission:ciudades edit')->only(['edit', 'update']);
        $this->middleware('permission:ciudades destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // buscar
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        $query = 'SELECT c.*, d.descripcion as departamento
                  FROM ciudades c
                  JOIN departamentos d ON c.id_departamento = d.id_departamento
                  WHERE 1=1';
        $params = [];

        // Aplicar filtro de estado
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $query .= ' AND c.estado = ?';
            $params[] = ($estadoFilter === '1' || $estadoFilter === 'true') ? true : false;
        }

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $query .= ' AND c.id_ciudad = ?';
                $params[] = (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];
                $query .= ' AND (c.descripcion ILIKE ? OR d.descripcion ILIKE ? OR cast(c.id_ciudad as text) ILIKE ?)';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
            }
        }
        
        $query .= ' ORDER BY c.id_ciudad DESC';

        $ciudades = DB::select($query, $params);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($ciudades);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($ciudades, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $ciudades = new LengthAwarePaginator(
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
            return view('ciudades.table')->with('ciudades', $ciudades);
        }


        return view('ciudades.index')->with('ciudades', $ciudades);
    }

    public function create()
    {
        // Mostrar departamentos en la vista utilizando la función pluck()
        $departamentos = DB::table('departamentos')->pluck('descripcion', 'id_departamento');

        return view('ciudades.create')->with('departamentos', $departamentos);
    }

    public function store(Request $request)
    {
        // Validar y guardar la nueva ciudad
        $input = $request->all();

        // Validación de los campos requeridos y existencia del departamento id
        $validacion = Validator::make(
            $input,
            [
                'descripcion' => 'required',
                'id_departamento' => 'required|exists:departamentos,id_departamento'
            ],
            [
                'descripcion.required' => 'El campo descripción es obligatorio.',
                'id_departamento.required' => 'El campo id_departamento es obligatorio.',
                'id_departamento.exists' => 'El id_departamento proporcionado no existe.'
            ]
        );
        // Si la validación falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion->errors());
        }

        // Validar que la descripción no esté duplicada en el mismo departamento
        $existe = DB::selectOne(
            'SELECT * FROM ciudades WHERE LOWER(descripcion) = LOWER(?)',
            [$input['descripcion']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una ciudad con esta descripción.'])->withInput();
        }

        // Guardar la nueva ciudad
        DB::insert(
            'INSERT INTO ciudades (descripcion, id_departamento, estado) VALUES (?, ?, ?)',
            [
                $input['descripcion'],
                $input['id_departamento'],
                true // Estado activo por defecto
            ]
        );
        // Redirigir a la lista de ciudades con un mensaje de éxito
        Alert::success('Éxito', 'Ciudad creada correctamente.');

        return redirect(route('ciudades.index'));
    }

    public function edit($id)
    {
        // Obtener la ciudad por su ID
        $ciudad = DB::selectOne('SELECT * FROM ciudades WHERE id_ciudad = ?', [$id]);

        if(empty($ciudad)) {
            Alert::error('Error', 'Ciudad no encontrada.');
            return redirect(route('ciudades.index'));
        }

        // Mostrar departamentos en la vista utilizando la función pluck()
        $departamentos = DB::table('departamentos')->pluck('descripcion', 'id_departamento');

        return view('ciudades.edit')->with('ciudades', $ciudad)
                                    ->with('departamentos', $departamentos);
    }

    public function update(Request $request, $id)
    {
        // Validar y actualizar la ciudad
        $input = $request->all();
        $ciudad = DB::selectOne('SELECT * FROM ciudades WHERE id_ciudad = ?', [$id]);

        // Si la ciudad no existe, redirigir con un mensaje de error
        if(empty($ciudad)) {
            Alert::error('Error', 'Ciudad no encontrada.');
            return redirect(route('ciudades.index'));
        }

        // Validación de los campos requeridos y existencia del departamento id
        $validacion = Validator::make(
            $input,
            [
                'descripcion' => 'required',
                'id_departamento' => 'required|exists:departamentos,id_departamento'
            ],
            [
                'descripcion.required' => 'El campo descripción es obligatorio.',
                'id_departamento.required' => 'El campo id_departamento es obligatorio.',
                'id_departamento.exists' => 'El id_departamento proporcionado no existe.'
            ]
        );
        // Si la validación falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion->errors());
        }

        // Validar que la descripción no esté duplicada 
        $existe = DB::selectOne(
            'SELECT * FROM ciudades WHERE LOWER(descripcion) = LOWER(?) AND id_ciudad != ?',
            [$input['descripcion'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una ciudad con esta descripción.'])->withInput();
        }

        // Actualizar la ciudad
        DB::update(
            'UPDATE ciudades SET descripcion = ?, id_departamento = ? WHERE id_ciudad = ?',
            [
                $input['descripcion'],
                $input['id_departamento'],
                $id
            ]
        );
        // Redirigir a la lista de ciudades con un mensaje de éxito
        Alert::success('Éxito', 'Ciudad actualizada correctamente.');

        return redirect(route('ciudades.index'));
    }

    public function destroy($id)
    {
        // Obtener la ciudad por su ID
        $ciudad = DB::selectOne('SELECT * FROM ciudades WHERE id_ciudad = ?', [$id]);

        // Si la ciudad no existe, redirigir con un mensaje de error
        if(empty($ciudad)) {
            Alert::error('Error', 'Ciudad no encontrada.');
            return redirect(route('ciudades.index'));
        }

        try {
            // Soft delete: cambiar el estado
            // Si el campo 'estado' no existe aún en la BD (antes de correr la migración), esto fallará si se ejecuta.
            // Asumimos que la migración se corre al final.
            // Para toggle:
            $nuevoEstado = isset($ciudad->estado) ? !$ciudad->estado : false; 
            // Si es null o no existe, asumimos que queremos desactivarlo (false). 
            // Pero si estamos implementando soft deletes, el default debería ser true.
            
            DB::update('UPDATE ciudades SET estado = ? WHERE id_ciudad = ?', [$nuevoEstado, $id]);
            
            $mensaje = $nuevoEstado ? 'Ciudad activada correctamente.' : 'Ciudad inactivada correctamente.';
            Alert::success('Éxito', $mensaje);
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }

        return redirect(route('ciudades.index'));
    }

    /**
     * Get ciudades by departamento (for AJAX requests)
     */
    public function getByDepartamento($id_departamento)
    {
        $ciudades = DB::select(
            'SELECT id_ciudad, descripcion
            FROM ciudades
            WHERE id_departamento = ?
            ORDER BY descripcion ASC',
            [$id_departamento]
        );

        // Format as key-value pairs for select options
        $result = [];
        foreach ($ciudades as $ciudad) {
            $result[$ciudad->id_ciudad] = $ciudad->descripcion;
        }

        return response()->json($result);
    }
}
