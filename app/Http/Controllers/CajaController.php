<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        // Obtener el término de búsqueda
        $buscar = $request->get('buscar');

        // Construir consulta con filtros de búsqueda por todos los campos visibles
        if ($buscar) {
            $cajas = DB::select(
                'SELECT c.*, s.descripcion as sucursal
                FROM cajas c
                JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.descripcion ILIKE ?
                OR s.descripcion ILIKE ?
                OR cast(c.punto_expedicion as text) ILIKE ?
                OR cast(c.ultima_factura_impresa as text) ILIKE ?
                OR cast(c.id_caja as text) ILIKE ?
                ORDER BY c.id_caja DESC',
                ['%' . $buscar . '%', '%' . $buscar . '%', '%' . $buscar . '%', '%' . $buscar . '%', '%' . $buscar . '%']
            );
        } else {
            $cajas = DB::select(
                'SELECT c.*, s.descripcion as sucursal
                FROM cajas c
                JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                ORDER BY c.id_caja DESC'
            );
        }

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

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('cajas.table')->with('cajas', $cajas);
        }

        return view('cajas.index')->with('cajas', $cajas);
    }
    public function create()
    {
        //Obtener sucursales
        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');
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
        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');
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
        //Validar si el producto no existe
        $cajas = DB::delete('DELETE FROM cajas WHERE id_caja = ?', [$id]);
        if (empty($cajas)) {
            Alert::toast('Caja no encontrada', 'error');
            return redirect()->route('cajas.index');
        }
        //Eliminar la caja de la base de datos
        DB::delete('DELETE FROM cajas WHERE id_caja = ?', [$id]);
        //Redirigir a la lista de productos con un mesaje de éxito
        Alert::toast('Caja eliminada con éxito', 'success');
        //flash('Caja eliminada con éxito');
        return redirect(route('cajas.index'));
    }
}
