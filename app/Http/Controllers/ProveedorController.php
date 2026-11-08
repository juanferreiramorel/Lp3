<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class ProveedorController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:proveedores index')->only(['index']);
        $this->middleware('permission:proveedores create')->only(['create', 'store']);
        $this->middleware('permission:proveedores edit')->only(['edit', 'update']);
        $this->middleware('permission:proveedores destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Obtener el término de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'id_proveedor', 'first' => 'desc'],
            'descripcion' => ['label' => 'Descripcion', 'sort' => 'descripcion'],
            'ruc' => ['label' => 'RUC', 'sort' => 'ruc'],
            'direccion' => ['label' => 'Direccion', 'sort' => 'direccion'],
            'telefono' => ['label' => 'Telefono', 'sort' => 'telefono'],
            'acciones' => ['label' => 'Acciones', 'width' => '140px'],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $proveedores = DB::select(
                    'SELECT * FROM proveedores
                    WHERE id_proveedor = ?
                    ORDER BY ' . $sort->orderBy(),
                    [(int)$searchConfig['term']]
                );
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];
                $proveedores = DB::select(
                    'SELECT * FROM proveedores
                    WHERE descripcion ILIKE ?
                    OR direccion ILIKE ?
                    OR telefono ILIKE ?
                    OR ruc ILIKE ?
                    OR cast(id_proveedor as text) ILIKE ?
                    ORDER BY ' . $sort->orderBy(),
                    ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%']
                );
            }
        } else {
            $proveedores = DB::select('SELECT * FROM proveedores ORDER BY ' . $sort->orderBy());
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($proveedores);        // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($proveedores, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $proveedores = new LengthAwarePaginator(
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
            return view('proveedores.table')
                ->with('proveedores', $proveedores)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        return view('proveedores.index')
            ->with('proveedores', $proveedores)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }
    public function create()
    {
        return view('proveedores.create');
    }
    public function store(Request $request)
    {
        //campos recibidos del formulario
        $input = $request->all();
        //validacion de los campos
        $validacion = Validator::make(
            $input,
        [
            'descripcion' => 'required' ,
            'telefono' => 'required'
        ],
        [
            'descripcion.required' => 'El campo descripcion es obligatorio',
            'telefono.required' => 'El campo telefono es obligatorio'
        ]);
        if ($validacion->fails()) {
            return back()->withErrors($validacion)->withInput();
        }

        // Validar que la descripción no esté duplicada
        $existe = DB::selectOne(
            'SELECT * FROM proveedores WHERE LOWER(descripcion) = LOWER(?)',
            [$input['descripcion']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un proveedor con esta descripción.'])->withInput();
        }

        //insertar proveedores en la base de datos
        DB::insert(
            'INSERT INTO proveedores (descripcion, ruc, direccion, telefono) values (?, ?, ?, ?)',
            [
                $input['descripcion'],
                $input['ruc'] ?? null,
                $input['direccion'],
                $input['telefono']
            ]);
            Alert::toast('Proveedor creado con exito', 'success');
            return redirect()->route('proveedores.index');
    }

    public function edit($id)
    {
        $proveedor = DB::selectOne('SELECT * FROM proveedores WHERE id_proveedor = ?', [$id]);
        if(empty($proveedor)){
            Alert::toast('Proveedor no encontrado', 'error');
            return redirect()->route('proveedores.index');
        }
        return view('proveedores.edit')->with('proveedores', $proveedor);
    }
    public function update(Request $request, $id)
    {
        $proveedor = DB::selectOne('SELECT * FROM proveedores WHERE id_proveedor = ?', [$id]);
        if(empty($proveedor)){
            Alert::toast('Proveedor no encontrado', 'error');
            return redirect()->route('proveedores.index');
        }
        $input = $request->all();
        $validacion = Validator::make(
            $input,
        [
            'descripcion' => 'required' ,
            'telefono' => 'required'
        ],
        [
            'descripcion.required' => 'El campo descripcion es obligatorio',
            'telefono.required' => 'El campo telefono es obligatorio'
        ]);
        if ($validacion->fails()) {
            return back()->withErrors($validacion)->withInput();
        }

        // Validar que la descripción no esté duplicada (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM proveedores WHERE LOWER(descripcion) = LOWER(?) AND id_proveedor != ?',
            [$input['descripcion'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un proveedor con esta descripción.'])->withInput();
        }

        DB::update('UPDATE proveedores
        SET descripcion = ?,
        ruc = ?,
        direccion = ?,
        telefono = ?
        WHERE id_proveedor = ?',
        [
        $input['descripcion'],
        $input['ruc'] ?? null,
        $input['direccion'],
        $input['telefono'],
        $id
    ]
);
        Alert::toast('El proveedor actualizado con exito', 'success');
        return redirect()->route('proveedores.index');
    }
    public function destroy($id)
    {
        $proveedor = DB::selectOne('SELECT * FROM proveedores WHERE id_proveedor = ?', [$id]);
        if(empty($proveedor)){
            Alert::error('Error', 'Proveedor no encontrado.');
            return redirect()->route('proveedores.index');
        }

        try {
            DB::delete('DELETE FROM proveedores WHERE id_proveedor = ?', [$id]);
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
        }

        return redirect()->route('proveedores.index');
    }
}
