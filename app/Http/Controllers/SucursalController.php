<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class SucursalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:sucursales index')->only(['index']);
        $this->middleware('permission:sucursales create')->only(['create', 'store']);
        $this->middleware('permission:sucursales edit')->only(['edit', 'update']);
        $this->middleware('permission:sucursales destroy')->only(['destroy']);
    }

    public function index(Request $request){
       // Obtener el término de búsqueda
       $buscar = $request->get('buscar');

       // Construir consulta con filtros de búsqueda por todos los campos visibles
       if ($buscar) {
           $sucursales = DB::select(
               'SELECT s.*, c.descripcion as ciudades
                FROM sucursales s
                JOIN ciudades c ON s.id_ciudad = c.id_ciudad
                WHERE s.descripcion ILIKE ?
                OR s.direccion ILIKE ?
                OR s.telefono ILIKE ?
                OR c.descripcion ILIKE ?
                OR cast(s.id_sucursal as text) ILIKE ?
                ORDER BY s.id_sucursal DESC',
               ['%' . $buscar . '%', '%' . $buscar . '%', '%' . $buscar . '%', '%' . $buscar . '%', '%' . $buscar . '%']
           );
       } else {
           $sucursales = DB::select(
               'SELECT s.*, c.descripcion as ciudades
                FROM sucursales s
                JOIN ciudades c ON s.id_ciudad = c.id_ciudad
                ORDER BY s.id_sucursal DESC'
           );
       }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($sucursales);         // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($sucursales, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $sucursales = new LengthAwarePaginator(
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
            return view('sucursales.table')->with('sucursales', $sucursales);
        }

        return view('sucursales.index')->with('sucursales', $sucursales);
    }
    public function create(){
        //Obtener ciudades
        $ciudades = DB::table('ciudades')->pluck('descripcion', 'id_ciudad');

        return view('sucursales.create')->with('ciudades', $ciudades);
        
    }
    public function store(Request $request){
        $input = $request->all();

        //Validar los datos
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'direccion' => 'required',
            'telefono' => 'required',
            'id_ciudad' => 'required|exists:ciudades,id_ciudad',
        ], [
            'descripcion.required' => 'La descripcion es obligatoria',
            'direccion.required' => 'La direccion es obligatoria',
            'telefono.required' => 'El telefono es obligatorio',
            'id_ciudad.required' => 'La ciudad es obligatoria',
            'id_ciudad.exists' => 'La ciudad no existe',
        ]);
        //si la validacion falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que la descripción no esté duplicada
        $existe = DB::selectOne(
            'SELECT * FROM sucursales WHERE LOWER(descripcion) = LOWER(?)',
            [$input['descripcion']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una sucursal con esta descripción.'])->withInput();
        }

        //Insertar el nuevo producto en la Base de datos
        DB::insert(
            'INSERT INTO sucursales (descripcion, direccion, telefono, id_ciudad) VALUES (?, ?, ?, ?)',
            [
                $input['descripcion'],
                $input['direccion'],
                $input['telefono'],
                $input['id_ciudad']
            ]
        );
        //Redirigir a la lista de sucursales con un mesaje de exito
        Alert::toast('Sucursal creada con exito', 'success');
        return redirect()->route('sucursales.index');   
    }
    public function edit($id){
        $sucursales = DB::selectOne('SELECT * FROM sucursales WHERE id_sucursal = ?', [$id]); //SELECTONE devuelve un objeto()
        if (empty($sucursales)) {
            Alert::toast('Sucursal no encontrada', 'error');
            return redirect()->route('sucursales.index');
        }
        //Obtener ciudades
        $ciudades = DB::table('ciudades')->pluck('descripcion', 'id_ciudad');
        return view('sucursales.edit')->with('sucursales', $sucursales)->with('ciudades', $ciudades);
    }
    public function update(Request $request, $id){
        $input = $request->all();
        //Obtener el producto de la base de datos  1 solo valor utilizando selectOne
        $sucursal = DB::selectOne('SELECT * FROM sucursales WHERE id_sucursal = ?', [$id]);
        //Validar si el producto no existe, redirigir con un mesaje de error
        if (empty($sucursal)) {
            Alert::toast('Sucursal no encontrada', 'error');
            return redirect()->route('sucursales.index');
        }
        //Validar los datos
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'direccion' => 'required',
            'telefono' => 'required',
            'id_ciudad' => 'required|exists:ciudades,id_ciudad',
        ], [
            'descripcion.required' => 'La descripcion es obligatoria',
            'direccion.required' => 'La direccion es obligatoria',
            'telefono.required' => 'El telefono es obligatorio',
            'id_ciudad.required' => 'La ciudad es obligatoria',
            'id_ciudad.exists' => 'La ciudad no existe',
        ]);
        //si la validacion falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que la descripción no esté duplicada (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM sucursales WHERE LOWER(descripcion) = LOWER(?) AND id_sucursal != ?',
            [$input['descripcion'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe una sucursal con esta descripción.'])->withInput();
        }

        //Actualizar el producto en la Base de datos
        DB::update(
            'UPDATE sucursales SET descripcion = ?, direccion = ?, telefono = ?, id_ciudad = ? WHERE id_sucursal = ?',
            [
                $input['descripcion'],
                $input['direccion'],
                $input['telefono'],
                $input['id_ciudad'],
                $id
            ]
        );
        //Redirigir a la lista de sucursales con un mesaje de exito
        Alert::toast('Sucursal actualizada con exito', 'success');
        return redirect()->route('sucursales.index');
    }
    public function destroy($id){
        //Validar si la sucursal existe
        $sucursal = DB::selectOne('SELECT * FROM sucursales WHERE id_sucursal = ?', [$id]);
        if (empty($sucursal)) {
            Alert::toast('Sucursal no encontrada', 'error');
            return redirect()->route('sucursales.index');
        }

        try {
            //Eliminar la sucursal de la base de datos
            DB::delete('DELETE FROM sucursales WHERE id_sucursal = ?', [$id]);
            Alert::toast('Sucursal eliminada con exito', 'success');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se puede eliminar esta sucursal porque está siendo utilizada en otros registros.');
        }

        return redirect(route('sucursales.index'));
    }
}
